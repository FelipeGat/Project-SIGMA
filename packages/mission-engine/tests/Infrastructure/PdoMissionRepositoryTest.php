<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Infrastructure;

use PHPUnit\Framework\TestCase;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionStatus;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\SubtaskStatus;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;
use Sigma\MissionEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\MissionEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\MissionEngine\Infrastructure\Pdo\PdoMissionRepository;

final class PdoMissionRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private PdoMissionRepository $repository;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->pdo = PdoTestConnection::connectOrSkip();
        $this->repository = new PdoMissionRepository($this->pdo);
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_migrations_apply_clean_and_running_twice_does_not_fail(): void
    {
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);

        self::assertTrue(true);
    }

    public function test_a_freshly_created_mission_survives_a_round_trip(): void
    {
        $plan = new Plan(
            [new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', 0)],
            PlanSource::Manual,
        );
        $mission = Mission::create(
            TenantId::generate(),
            null,
            CorrelationId::generate(),
            null,
            'Fechar negócio com o cliente Brenno',
            $plan,
            new Actor(ActorType::User, 'felipe'),
            1,
            $this->now,
        );

        $this->repository->save($mission);
        $reloaded = $this->repository->find($mission->id());

        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->id()->equals($mission->id()));
        self::assertTrue($reloaded->tenantId()->equals($mission->tenantId()));
        self::assertNull($reloaded->workspaceId());
        self::assertNull($reloaded->intentId());
        self::assertSame($mission->objective(), $reloaded->objective());
        self::assertSame(MissionStatus::Created, $reloaded->status());
        self::assertSame(1, $reloaded->autonomyCeiling());
        self::assertSame([], $reloaded->subtasks());
        self::assertCount(1, $reloaded->history());
    }

    public function test_find_returns_null_for_an_unknown_id(): void
    {
        $mission = Mission::create(
            TenantId::generate(),
            WorkspaceId::generate(),
            CorrelationId::generate(),
            null,
            'Objetivo',
            new Plan([new SubtaskCandidate('X', null, null, 0)], PlanSource::Manual),
            new Actor(ActorType::System, 'scheduler'),
            0,
            $this->now,
        );

        self::assertNull($this->repository->find($mission->id()));
    }

    public function test_a_mission_with_approval_gate_retry_compensation_and_history_survives_a_full_round_trip(): void
    {
        $plan = new Plan(
            [
                new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', 2),
                new SubtaskCandidate('Registrar no Funil', 'agent.sales', 'capability.funil.create', 0),
            ],
            PlanSource::Manual,
        );
        $mission = Mission::create(
            TenantId::generate(),
            WorkspaceId::generate(),
            CorrelationId::generate(),
            null,
            'Fechar negócio com o cliente Brenno',
            $plan,
            new Actor(ActorType::User, 'felipe'),
            1,
            $this->now,
        );
        $this->repository->save($mission);

        // Primeira Subtask exige autonomyCeiling 2, Mission só tem 1 —
        // pede aprovação.
        $mission = $this->repository->find($mission->id());
        $mission->advanceToNextSubtask($this->now);
        $this->repository->save($mission);

        $mission = $this->repository->find($mission->id());
        self::assertSame(MissionStatus::PendingApproval, $mission->status());
        self::assertNotNull($mission->pendingApprovalGate());

        $decidedAt = new \DateTimeImmutable('2026-08-04T10:05:00+00:00');
        $mission->approve('felipe', $decidedAt);
        $this->repository->save($mission);

        $mission = $this->repository->find($mission->id());
        self::assertSame(MissionStatus::InProgress, $mission->status());
        self::assertNull($mission->pendingApprovalGate());
        self::assertCount(1, $mission->approvalGates());
        self::assertSame('felipe', $mission->approvalGates()[0]->decidedBy());

        $subtaskId = $mission->subtasks()[0]->id();
        $mission->assignSubtask($subtaskId);
        $mission->startSubtaskExecution($subtaskId);
        $mission->retrySubtask($subtaskId, 'timeout de rede', $this->now);
        $mission->retrySubtask($subtaskId, 'timeout de novo', $this->now);
        $this->repository->save($mission);

        $mission = $this->repository->find($mission->id());
        $subtask = $mission->findSubtask($subtaskId);
        self::assertSame(SubtaskStatus::Executing, $subtask->status());
        self::assertCount(2, $subtask->retryAttempts());
        self::assertSame(1, $subtask->retryAttempts()[0]->attemptNumber);
        self::assertSame(2, $subtask->retryAttempts()[1]->attemptNumber);
        self::assertSame('timeout de rede', $subtask->retryAttempts()[0]->reason);

        // Falha definitiva com efeito já produzido → Compensating → compensa → Failed.
        $mission->failSubtask($subtaskId, true, $this->now);
        $this->repository->save($mission);

        $mission = $this->repository->find($mission->id());
        self::assertSame(MissionStatus::Compensating, $mission->status());

        $mission->compensateSubtask($subtaskId, 'Cancelou o orçamento enviado por engano', true, $this->now);
        $this->repository->save($mission);

        $mission = $this->repository->find($mission->id());
        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertNotNull($mission->finishedAt());
        self::assertCount(1, $mission->compensations());
        self::assertSame('Cancelou o orçamento enviado por engano', $mission->compensations()[0]->action);
        self::assertSame(SubtaskStatus::Compensated, $mission->findSubtask($subtaskId)->status());

        // Histórico completo, na ordem certa: Created, PendingApproval, InProgress, Compensating, Failed.
        $statuses = array_map(static fn ($entry) => $entry->status, $mission->history());
        self::assertSame(
            [
                MissionStatus::Created,
                MissionStatus::PendingApproval,
                MissionStatus::InProgress,
                MissionStatus::Compensating,
                MissionStatus::Failed,
            ],
            $statuses,
        );
    }

    public function test_a_subtask_result_and_plan_survive_the_round_trip_through_json_columns(): void
    {
        $plan = new Plan(
            [
                new SubtaskCandidate('Passo A', 'agent.x', 'capability.a', 0),
                new SubtaskCandidate('Passo B', null, null, 1),
            ],
            PlanSource::Manual,
        );
        $mission = Mission::create(
            TenantId::generate(),
            null,
            CorrelationId::generate(),
            null,
            'Objetivo com Plan de dois passos',
            $plan,
            new Actor(ActorType::Agent, 'agent-007'),
            2,
            $this->now,
        );
        $mission->advanceToNextSubtask($this->now);
        $subtaskId = $mission->subtasks()[0]->id();
        $mission->assignSubtask($subtaskId);
        $mission->startSubtaskExecution($subtaskId);
        $mission->validateSubtask($subtaskId, ['sent' => true, 'amount' => 1500.50, 'items' => ['a', 'b']]);
        $this->repository->save($mission);

        $reloaded = $this->repository->find($mission->id());

        self::assertSame(PlanSource::Manual, $reloaded->plan()->source);
        self::assertCount(2, $reloaded->plan()->subtaskCandidates);
        self::assertSame('Passo B', $reloaded->plan()->subtaskCandidates[1]->description);
        self::assertSame(1, $reloaded->plan()->subtaskCandidates[1]->requiredAutonomyLevel);

        $subtask = $reloaded->findSubtask($subtaskId);
        self::assertSame(SubtaskStatus::Validated, $subtask->status());
        self::assertSame(['sent' => true, 'amount' => 1500.50, 'items' => ['a', 'b']], $subtask->result());

        self::assertSame(ActorType::Agent, $reloaded->actor()->type);
        self::assertSame('agent-007', $reloaded->actor()->id);
    }
}
