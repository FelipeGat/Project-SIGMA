<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Domain\Event\MissionApproved;
use Sigma\MissionEngine\Domain\Event\MissionApprovalRequested;
use Sigma\MissionEngine\Domain\Event\MissionCancelled;
use Sigma\MissionEngine\Domain\Event\MissionCompensationFinished;
use Sigma\MissionEngine\Domain\Event\MissionCompensationStarted;
use Sigma\MissionEngine\Domain\Event\MissionCreated;
use Sigma\MissionEngine\Domain\Event\MissionFailed;
use Sigma\MissionEngine\Domain\Event\MissionFinished;
use Sigma\MissionEngine\Domain\Event\MissionRejected;
use Sigma\MissionEngine\Domain\Event\MissionStarted;
use Sigma\MissionEngine\Domain\Event\SubtaskCompensated;
use Sigma\MissionEngine\Domain\Event\SubtaskRetried;
use Sigma\MissionEngine\Domain\Event\SubtasksCreated;

/**
 * A unidade de trabalho do SIGMA (MISSION_MODEL.md) — Aggregate Root
 * que carrega ciclo de vida, aprovação, retries e compensação
 * (MISSION_MANIFESTO.md, ADR-0089 a ADR-0093). Uma Mission só passa a
 * existir quando um `Plan` já está pronto — a interpretação de uma
 * Intent e o planejamento que a decompõe acontecem antes, fora deste
 * Aggregate (ADR-0089).
 */
final class Mission
{
    use RecordsDomainEvents;

    private MissionStatus $status;

    /** @var list<Subtask> */
    private array $subtasks = [];

    /** @var list<ApprovalGate> */
    private array $approvalGates = [];

    private ?ApprovalGate $pendingApprovalGate = null;

    /** @var list<Compensation> */
    private array $compensations = [];

    /** @var list<MissionHistoryEntry> */
    private array $history = [];

    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $finishedAt = null;

    private function __construct(
        private readonly MissionId $id,
        private readonly TenantId $tenantId,
        private readonly ?WorkspaceId $workspaceId,
        private readonly CorrelationId $correlationId,
        private readonly ?IntentId $intentId,
        private readonly string $objective,
        private readonly Plan $plan,
        private readonly Actor $actor,
        private readonly int $autonomyCeiling,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        TenantId $tenantId,
        ?WorkspaceId $workspaceId,
        CorrelationId $correlationId,
        ?IntentId $intentId,
        string $objective,
        Plan $plan,
        Actor $actor,
        int $autonomyCeiling,
        \DateTimeImmutable $now,
    ): self {
        if (trim($objective) === '') {
            throw new SigmaException('Mission.objective não pode ser vazio.', 'mission.invalid_objective');
        }

        if ($autonomyCeiling < 0 || $autonomyCeiling > 3) {
            throw new SigmaException('autonomyCeiling precisa estar entre 0 e 3.', 'mission.invalid_autonomy_ceiling');
        }

        $mission = new self(
            MissionId::generate(),
            $tenantId,
            $workspaceId,
            $correlationId,
            $intentId,
            $objective,
            $plan,
            $actor,
            $autonomyCeiling,
            $now,
        );

        $mission->status = MissionStatus::Created;
        $mission->history[] = new MissionHistoryEntry(MissionStatus::Created, $now);
        $mission->record(new MissionCreated($mission->id, $correlationId, $tenantId, $workspaceId, $intentId));

        return $mission;
    }

    /**
     * @param list<Subtask> $subtasks
     * @param list<ApprovalGate> $approvalGates
     * @param list<Compensation> $compensations
     * @param list<MissionHistoryEntry> $history
     */
    public static function reconstitute(
        MissionId $id,
        TenantId $tenantId,
        ?WorkspaceId $workspaceId,
        CorrelationId $correlationId,
        ?IntentId $intentId,
        string $objective,
        Plan $plan,
        Actor $actor,
        int $autonomyCeiling,
        MissionStatus $status,
        array $subtasks,
        array $approvalGates,
        ?ApprovalGate $pendingApprovalGate,
        array $compensations,
        array $history,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $startedAt,
        ?\DateTimeImmutable $finishedAt,
    ): self {
        $mission = new self(
            $id,
            $tenantId,
            $workspaceId,
            $correlationId,
            $intentId,
            $objective,
            $plan,
            $actor,
            $autonomyCeiling,
            $createdAt,
        );

        $mission->status = $status;
        $mission->subtasks = $subtasks;
        $mission->approvalGates = $approvalGates;
        $mission->pendingApprovalGate = $pendingApprovalGate;
        $mission->compensations = $compensations;
        $mission->history = $history;
        $mission->startedAt = $startedAt;
        $mission->finishedAt = $finishedAt;

        return $mission;
    }

    // ----- Fluxo 1/2 — criação de Subtask a partir do Plan + gate de autonomia -----

    /**
     * Puxa a próxima SubtaskCandidate do `Plan` (na ordem em que aparecem),
     * cria a `Subtask` correspondente (status `Pending`) e avalia se o
     * `autonomyCeiling` desta Mission é suficiente para ela prosseguir sem
     * confirmação humana — mesma mecânica tanto para a primeira Subtask
     * (Fluxo 1) quanto para as seguintes (Fluxo 2), ver MISSION_LIFECYCLE.md.
     */
    public function advanceToNextSubtask(\DateTimeImmutable $now): Subtask
    {
        $this->assertStatusIn([MissionStatus::Created, MissionStatus::InProgress], 'avançar para a próxima Subtask');

        $nextIndex = count($this->subtasks);
        if (!isset($this->plan->subtaskCandidates[$nextIndex])) {
            throw new SigmaException('Não há mais nenhuma Subtask candidata no Plan.', 'mission.no_more_subtask_candidates');
        }

        $candidate = $this->plan->subtaskCandidates[$nextIndex];
        $subtask = Subtask::fromCandidate(SubtaskId::generate(), $candidate);
        $this->subtasks[] = $subtask;
        $this->record(new SubtasksCreated($this->id, [$subtask->id()]));

        if ($candidate->requiredAutonomyLevel > $this->autonomyCeiling) {
            $gate = new ApprovalGate(
                ApprovalGateId::generate(),
                sprintf(
                    'autonomyCeiling (%d) insuficiente para a Subtask "%s" (requer %d).',
                    $this->autonomyCeiling,
                    $candidate->description,
                    $candidate->requiredAutonomyLevel,
                ),
                $now,
            );
            $this->approvalGates[] = $gate;
            $this->pendingApprovalGate = $gate;
            $this->transitionTo(MissionStatus::PendingApproval, $now);
            $this->record(new MissionApprovalRequested($this->id, $gate->id(), $gate->reason));

            return $subtask;
        }

        if ($this->status === MissionStatus::Created) {
            $this->startedAt = $now;
            $this->transitionTo(MissionStatus::InProgress, $now);
            $this->record(new MissionStarted($this->id));
        }

        return $subtask;
    }

    public function approve(string $decidedBy, \DateTimeImmutable $now): void
    {
        $gate = $this->requirePendingGate();
        $gate->approve($decidedBy, $now);
        $this->pendingApprovalGate = null;

        if ($this->startedAt === null) {
            $this->startedAt = $now;
        }

        $this->transitionTo(MissionStatus::InProgress, $now);
        $this->record(new MissionApproved($this->id, $gate->id(), $decidedBy));
    }

    public function reject(string $decidedBy, \DateTimeImmutable $now): void
    {
        $gate = $this->requirePendingGate();
        $gate->reject($decidedBy, $now);
        $this->pendingApprovalGate = null;
        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Cancelled, $now);
        $this->record(new MissionRejected($this->id, $gate->id(), $decidedBy));
    }

    // ----- Fluxo 2 — execução e retry de Subtask -----

    public function assignSubtask(SubtaskId $id): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'atribuir Subtask');
        $this->findSubtask($id)->assign();
    }

    public function startSubtaskExecution(SubtaskId $id): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'iniciar execução de Subtask');
        $this->findSubtask($id)->startExecution();
    }

    public function retrySubtask(SubtaskId $id, string $reason, \DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'repetir Subtask');
        $attempt = $this->findSubtask($id)->retry($reason, $now);
        $this->record(new SubtaskRetried($this->id, $id, $attempt->attemptNumber, $reason));
    }

    public function validateSubtask(SubtaskId $id, mixed $result): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'validar Subtask');
        $this->findSubtask($id)->validate($result);
    }

    /**
     * Falha definitiva de uma Subtask (política de retry já esgotada).
     * `$hasProducedEffect` decide o desfecho — Fluxo 2, etapa 4 de
     * MISSION_LIFECYCLE.md: com efeito colateral já produzido, a Mission
     * inteira entra em `Compensating` (Fluxo 3); sem efeito, vai direto a
     * `Failed`.
     */
    public function failSubtask(SubtaskId $id, bool $hasProducedEffect, \DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'reportar falha de Subtask');
        $this->findSubtask($id)->fail();

        if ($hasProducedEffect) {
            $this->transitionTo(MissionStatus::Compensating, $now);
            $this->record(new MissionCompensationStarted($this->id, $id));

            return;
        }

        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Failed, $now);
        $this->record(new MissionFailed($this->id, $id));
    }

    // ----- Fluxo 3 — compensação -----

    /**
     * Registra a Compensation e conclui — uma Mission em `Compensating`
     * sempre termina em `Failed`, nunca em `Completed`, tenha a
     * compensação em si sido `Compensated` ou `CompensationFailed`
     * (MISSION_LIFECYCLE.md — Fluxo 3, ADR-0091). Esta Release trata só
     * uma Subtask em compensação por vez (modelo de granularidade grossa).
     */
    public function compensateSubtask(SubtaskId $id, string $action, bool $succeeded, \DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::Compensating, 'compensar Subtask');

        $result = $succeeded ? CompensationResult::Compensated : CompensationResult::CompensationFailed;
        $this->compensations[] = new Compensation($id, $action, $now, $result);
        $this->findSubtask($id)->compensate();
        $this->record(new SubtaskCompensated($this->id, $id, $result));

        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Failed, $now);
        $this->record(new MissionCompensationFinished($this->id));
    }

    // ----- Fluxo 4 — validação final e conclusão -----

    public function beginValidation(\DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::InProgress, 'iniciar validação final');

        if ($this->subtasks === []) {
            throw new SigmaException('Não é possível validar uma Mission sem nenhuma Subtask.', 'mission.no_subtasks_to_validate');
        }

        foreach ($this->subtasks as $subtask) {
            if ($subtask->status() !== SubtaskStatus::Validated) {
                throw new SigmaException(
                    'Só é possível iniciar a validação final quando todas as Subtasks estão Validated.',
                    'mission.subtasks_not_validated',
                );
            }
        }

        $this->transitionTo(MissionStatus::Validating, $now);
    }

    public function passValidation(\DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::Validating, 'concluir validação');
        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Completed, $now);
        $this->record(new MissionFinished($this->id));
    }

    /**
     * A validação final não passou — MISSION_LIFECYCLE.md (Fluxo 4) trata
     * isto "como falha de Subtask" (mesma lógica do Fluxo 2, etapa 4): a
     * Subtask apontada como origem do efeito que reprovou na validação
     * final é quem a Compensation eventual referencia — mesmo que seu
     * próprio status já fosse `Validated` (achado real da Implementation,
     * ver Decision Log da Release 5B e `Subtask::compensate()`).
     */
    public function failValidation(SubtaskId $faultingSubtaskId, bool $hasProducedEffect, \DateTimeImmutable $now): void
    {
        $this->assertStatus(MissionStatus::Validating, 'reprovar validação final');
        $this->findSubtask($faultingSubtaskId);

        if ($hasProducedEffect) {
            $this->transitionTo(MissionStatus::Compensating, $now);
            $this->record(new MissionCompensationStarted($this->id, $faultingSubtaskId));

            return;
        }

        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Failed, $now);
        $this->record(new MissionFailed($this->id, $faultingSubtaskId));
    }

    // ----- Cancelamento -----

    public function cancel(string $reason, \DateTimeImmutable $now): void
    {
        $this->assertStatusIn([
            MissionStatus::Created,
            MissionStatus::PendingApproval,
            MissionStatus::InProgress,
            MissionStatus::Compensating,
            MissionStatus::Validating,
        ], 'cancelar');

        $this->finishedAt = $now;
        $this->transitionTo(MissionStatus::Cancelled, $now);
        $this->record(new MissionCancelled($this->id, $reason));
    }

    // ----- Consultas -----

    public function findSubtask(SubtaskId $id): Subtask
    {
        foreach ($this->subtasks as $subtask) {
            if ($subtask->id()->equals($id)) {
                return $subtask;
            }
        }

        throw new SigmaException('Subtask não encontrada nesta Mission.', 'mission.subtask_not_found');
    }

    public function id(): MissionId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function workspaceId(): ?WorkspaceId
    {
        return $this->workspaceId;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function intentId(): ?IntentId
    {
        return $this->intentId;
    }

    public function objective(): string
    {
        return $this->objective;
    }

    public function plan(): Plan
    {
        return $this->plan;
    }

    public function actor(): Actor
    {
        return $this->actor;
    }

    public function autonomyCeiling(): int
    {
        return $this->autonomyCeiling;
    }

    public function status(): MissionStatus
    {
        return $this->status;
    }

    /** @return list<Subtask> */
    public function subtasks(): array
    {
        return $this->subtasks;
    }

    /** @return list<ApprovalGate> */
    public function approvalGates(): array
    {
        return $this->approvalGates;
    }

    public function pendingApprovalGate(): ?ApprovalGate
    {
        return $this->pendingApprovalGate;
    }

    /** @return list<Compensation> */
    public function compensations(): array
    {
        return $this->compensations;
    }

    /** @return list<MissionHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function startedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function finishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    // ----- Internos -----

    private function requirePendingGate(): ApprovalGate
    {
        if ($this->pendingApprovalGate === null) {
            throw new SigmaException('Esta Mission não tem ApprovalGate pendente.', 'mission.no_pending_approval_gate');
        }

        return $this->pendingApprovalGate;
    }

    private function transitionTo(MissionStatus $status, \DateTimeImmutable $now): void
    {
        $this->status = $status;
        $this->history[] = new MissionHistoryEntry($status, $now);
    }

    private function assertStatus(MissionStatus $expected, string $action): void
    {
        $this->assertStatusIn([$expected], $action);
    }

    /** @param list<MissionStatus> $expected */
    private function assertStatusIn(array $expected, string $action): void
    {
        if (!in_array($this->status, $expected, true)) {
            $labels = implode('" ou "', array_map(static fn (MissionStatus $s) => $s->value, $expected));
            throw new SigmaException(
                sprintf('Mission precisa estar "%s" para "%s" — está "%s".', $labels, $action, $this->status->value),
                'mission.invalid_status_transition',
            );
        }
    }
}
