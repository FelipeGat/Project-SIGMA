<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\CorrelationId;
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
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionStatus;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\SubtaskStatus;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

final class MissionTest extends TestCase
{
    private TenantId $tenantId;
    private WorkspaceId $workspaceId;
    private CorrelationId $correlationId;
    private Actor $actor;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->workspaceId = WorkspaceId::generate();
        $this->correlationId = CorrelationId::generate();
        $this->actor = new Actor(ActorType::User, 'felipe');
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    private function onePlan(int $requiredAutonomyLevel = 0): Plan
    {
        return new Plan(
            [new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', $requiredAutonomyLevel)],
            PlanSource::Manual,
        );
    }

    private function twoStepPlan(): Plan
    {
        return new Plan(
            [
                new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', 0),
                new SubtaskCandidate('Registrar no Funil', 'agent.sales', 'capability.funil.create', 0),
            ],
            PlanSource::Manual,
        );
    }

    private function createMission(?Plan $plan = null, int $autonomyCeiling = 1): Mission
    {
        return Mission::create(
            $this->tenantId,
            $this->workspaceId,
            $this->correlationId,
            null,
            'Fechar negócio com o cliente Brenno',
            $plan ?? $this->onePlan(),
            $this->actor,
            $autonomyCeiling,
            $this->now,
        );
    }

    // ----- Criação -----

    public function test_create_starts_in_created_status_and_records_mission_created(): void
    {
        $mission = $this->createMission();

        self::assertSame(MissionStatus::Created, $mission->status());
        self::assertSame([], $mission->subtasks());
        self::assertCount(1, $mission->history());
        self::assertSame(MissionStatus::Created, $mission->history()[0]->status);

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionCreated::class, $events[0]);
        self::assertSame('mission.created', $events[0]->name());
    }

    public function test_create_rejects_empty_objective(): void
    {
        $this->expectException(SigmaException::class);
        $this->expectExceptionCode(0);

        Mission::create(
            $this->tenantId,
            $this->workspaceId,
            $this->correlationId,
            null,
            '   ',
            $this->onePlan(),
            $this->actor,
            1,
            $this->now,
        );
    }

    public function test_create_rejects_autonomy_ceiling_out_of_range(): void
    {
        $this->expectException(SigmaException::class);

        Mission::create(
            $this->tenantId,
            $this->workspaceId,
            $this->correlationId,
            null,
            'Objetivo válido',
            $this->onePlan(),
            $this->actor,
            4,
            $this->now,
        );
    }

    public function test_workspace_and_intent_are_optional(): void
    {
        $mission = Mission::create(
            $this->tenantId,
            null,
            $this->correlationId,
            null,
            'Reindexar Knowledge',
            $this->onePlan(),
            new Actor(ActorType::System, 'scheduler'),
            0,
            $this->now,
        );

        self::assertNull($mission->workspaceId());
        self::assertNull($mission->intentId());
    }

    // ----- Fluxo 1/2 — avançar Subtask + gate de autonomia -----

    public function test_advancing_with_sufficient_autonomy_starts_the_mission(): void
    {
        $mission = $this->createMission($this->onePlan(requiredAutonomyLevel: 1), autonomyCeiling: 1);
        $mission->pullDomainEvents();

        $subtask = $mission->advanceToNextSubtask($this->now);

        self::assertSame(MissionStatus::InProgress, $mission->status());
        self::assertSame($this->now, $mission->startedAt());
        self::assertSame(SubtaskStatus::Pending, $subtask->status());
        self::assertCount(1, $mission->subtasks());

        $events = $mission->pullDomainEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(SubtasksCreated::class, $events[0]);
        self::assertInstanceOf(MissionStarted::class, $events[1]);
    }

    public function test_advancing_with_insufficient_autonomy_requests_approval(): void
    {
        $mission = $this->createMission($this->onePlan(requiredAutonomyLevel: 2), autonomyCeiling: 1);
        $mission->pullDomainEvents();

        $mission->advanceToNextSubtask($this->now);

        self::assertSame(MissionStatus::PendingApproval, $mission->status());
        self::assertNotNull($mission->pendingApprovalGate());
        self::assertCount(1, $mission->approvalGates());

        $events = $mission->pullDomainEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(SubtasksCreated::class, $events[0]);
        self::assertInstanceOf(MissionApprovalRequested::class, $events[1]);
    }

    public function test_advancing_a_second_subtask_while_in_progress_does_not_publish_mission_started_again(): void
    {
        $mission = $this->createMission($this->twoStepPlan(), autonomyCeiling: 1);
        $mission->advanceToNextSubtask($this->now);
        $mission->pullDomainEvents();

        $mission->advanceToNextSubtask($this->now);

        self::assertSame(MissionStatus::InProgress, $mission->status());
        self::assertCount(2, $mission->subtasks());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SubtasksCreated::class, $events[0]);
    }

    public function test_advancing_beyond_the_plan_throws(): void
    {
        $mission = $this->createMission(autonomyCeiling: 1);

        $mission->advanceToNextSubtask($this->now);

        $this->expectException(SigmaException::class);
        $mission->advanceToNextSubtask($this->now);
    }

    public function test_advancing_a_pending_approval_mission_throws(): void
    {
        $mission = $this->createMission($this->onePlan(requiredAutonomyLevel: 2), autonomyCeiling: 1);
        $mission->advanceToNextSubtask($this->now);

        $this->expectException(SigmaException::class);
        $mission->advanceToNextSubtask($this->now);
    }

    // ----- Aprovação -----

    public function test_approve_resolves_the_gate_and_resumes(): void
    {
        $mission = $this->createMission($this->onePlan(requiredAutonomyLevel: 2), autonomyCeiling: 1);
        $mission->advanceToNextSubtask($this->now);
        $mission->pullDomainEvents();

        $decidedAt = new \DateTimeImmutable('2026-08-04T10:10:00+00:00');
        $mission->approve('felipe', $decidedAt);

        self::assertSame(MissionStatus::InProgress, $mission->status());
        self::assertNull($mission->pendingApprovalGate());
        self::assertSame($decidedAt, $mission->startedAt());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionApproved::class, $events[0]);
    }

    public function test_reject_cancels_the_mission(): void
    {
        $mission = $this->createMission($this->onePlan(requiredAutonomyLevel: 2), autonomyCeiling: 1);
        $mission->advanceToNextSubtask($this->now);
        $mission->pullDomainEvents();

        $mission->reject('felipe', $this->now);

        self::assertSame(MissionStatus::Cancelled, $mission->status());
        self::assertSame($this->now, $mission->finishedAt());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionRejected::class, $events[0]);
    }

    public function test_approve_without_a_pending_gate_throws(): void
    {
        $mission = $this->createMission();

        $this->expectException(SigmaException::class);
        $mission->approve('felipe', $this->now);
    }

    // ----- Fluxo 2 — execução, retry -----

    public function test_full_execution_path_to_validated(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->pullDomainEvents();

        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());

        $attemptAt = new \DateTimeImmutable('2026-08-04T10:01:00+00:00');
        $mission->retrySubtask($subtask->id(), 'timeout', $attemptAt);

        $mission->validateSubtask($subtask->id(), ['ok' => true]);

        self::assertSame(SubtaskStatus::Validated, $mission->findSubtask($subtask->id())->status());
        self::assertSame(MissionStatus::InProgress, $mission->status());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SubtaskRetried::class, $events[0]);
        self::assertSame(1, $events[0]->attemptNumber);
    }

    public function test_find_subtask_throws_when_absent(): void
    {
        $mission = $this->createMission();

        $this->expectException(SigmaException::class);
        $mission->findSubtask(\Sigma\MissionEngine\Domain\SubtaskId::generate());
    }

    // ----- Falha sem efeito → Failed direto -----

    public function test_fail_subtask_without_produced_effect_goes_straight_to_failed(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->pullDomainEvents();

        $finishedAt = new \DateTimeImmutable('2026-08-04T10:02:00+00:00');
        $mission->failSubtask($subtask->id(), false, $finishedAt);

        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertSame($finishedAt, $mission->finishedAt());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionFailed::class, $events[0]);
    }

    // ----- Falha com efeito → Compensating → Failed -----

    public function test_fail_subtask_with_produced_effect_starts_compensation(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->pullDomainEvents();

        $mission->failSubtask($subtask->id(), true, $this->now);

        self::assertSame(MissionStatus::Compensating, $mission->status());
        self::assertNull($mission->finishedAt());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionCompensationStarted::class, $events[0]);
    }

    public function test_compensating_a_subtask_always_finishes_in_failed_even_when_compensation_succeeds(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->failSubtask($subtask->id(), true, $this->now);
        $mission->pullDomainEvents();

        $finishedAt = new \DateTimeImmutable('2026-08-04T10:05:00+00:00');
        $mission->compensateSubtask($subtask->id(), 'Cancelou o pedido criado por engano', true, $finishedAt);

        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertSame($finishedAt, $mission->finishedAt());
        self::assertCount(1, $mission->compensations());

        $events = $mission->pullDomainEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(SubtaskCompensated::class, $events[0]);
        self::assertInstanceOf(MissionCompensationFinished::class, $events[1]);
    }

    public function test_compensating_a_subtask_also_finishes_in_failed_when_compensation_itself_fails(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->failSubtask($subtask->id(), true, $this->now);

        $mission->compensateSubtask($subtask->id(), 'Tentou cancelar, mas o fornecedor já processou', false, $this->now);

        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertSame(
            \Sigma\MissionEngine\Domain\CompensationResult::CompensationFailed,
            $mission->compensations()[0]->result,
        );
    }

    // ----- Fluxo 4 — validação final -----

    public function test_begin_validation_requires_every_subtask_validated(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());

        $this->expectException(SigmaException::class);
        $mission->beginValidation($this->now);
    }

    public function test_full_happy_path_to_completed(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->validateSubtask($subtask->id(), 'done');

        $mission->beginValidation($this->now);
        self::assertSame(MissionStatus::Validating, $mission->status());

        $mission->pullDomainEvents();
        $finishedAt = new \DateTimeImmutable('2026-08-04T10:10:00+00:00');
        $mission->passValidation($finishedAt);

        self::assertSame(MissionStatus::Completed, $mission->status());
        self::assertSame($finishedAt, $mission->finishedAt());

        $events = $mission->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MissionFinished::class, $events[0]);
    }

    public function test_fail_validation_without_effect_goes_to_failed(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->validateSubtask($subtask->id(), 'done');
        $mission->beginValidation($this->now);
        $mission->pullDomainEvents();

        $mission->failValidation($subtask->id(), false, $this->now);

        self::assertSame(MissionStatus::Failed, $mission->status());

        $events = $mission->pullDomainEvents();
        self::assertInstanceOf(MissionFailed::class, $events[0]);
    }

    public function test_fail_validation_with_effect_starts_compensation_even_though_subtask_was_validated(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->validateSubtask($subtask->id(), 'done');
        $mission->beginValidation($this->now);
        $mission->pullDomainEvents();

        $mission->failValidation($subtask->id(), true, $this->now);
        self::assertSame(MissionStatus::Compensating, $mission->status());

        // A Subtask já estava Validated — compensate() precisa aceitar essa origem.
        $mission->compensateSubtask($subtask->id(), 'Desfez o efeito encontrado na validação final', true, $this->now);
        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertSame(SubtaskStatus::Compensated, $mission->findSubtask($subtask->id())->status());
    }

    // ----- Cancelamento -----

    public function test_cancel_from_created(): void
    {
        $mission = $this->createMission();
        $mission->pullDomainEvents();

        $mission->cancel('Cliente desistiu', $this->now);

        self::assertSame(MissionStatus::Cancelled, $mission->status());
        $events = $mission->pullDomainEvents();
        self::assertInstanceOf(MissionCancelled::class, $events[0]);
    }

    public function test_cancel_from_a_terminal_status_throws(): void
    {
        $mission = $this->createMission();
        $mission->cancel('Cliente desistiu', $this->now);

        $this->expectException(SigmaException::class);
        $mission->cancel('de novo', $this->now);
    }

    // ----- Histórico -----

    public function test_history_accumulates_every_transition(): void
    {
        $mission = $this->createMission();
        $subtask = $mission->advanceToNextSubtask($this->now);
        $mission->assignSubtask($subtask->id());
        $mission->startSubtaskExecution($subtask->id());
        $mission->validateSubtask($subtask->id(), 'done');
        $mission->beginValidation($this->now);
        $mission->passValidation($this->now);

        $statuses = array_map(static fn ($entry) => $entry->status, $mission->history());

        self::assertSame(
            [
                MissionStatus::Created,
                MissionStatus::InProgress,
                MissionStatus::Validating,
                MissionStatus::Completed,
            ],
            $statuses,
        );
    }
}
