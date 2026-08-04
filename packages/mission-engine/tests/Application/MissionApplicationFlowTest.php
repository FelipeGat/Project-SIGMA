<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MissionEngine\Application\UseCase\AdvanceMissionToNextSubtask;
use Sigma\MissionEngine\Application\UseCase\ApproveMission;
use Sigma\MissionEngine\Application\UseCase\AssignSubtask;
use Sigma\MissionEngine\Application\UseCase\BeginMissionValidation;
use Sigma\MissionEngine\Application\UseCase\CancelMission;
use Sigma\MissionEngine\Application\UseCase\CompensateSubtask;
use Sigma\MissionEngine\Application\UseCase\CreateMission;
use Sigma\MissionEngine\Application\UseCase\FailMissionValidation;
use Sigma\MissionEngine\Application\UseCase\FailSubtask;
use Sigma\MissionEngine\Application\UseCase\GetMission;
use Sigma\MissionEngine\Application\UseCase\PassMissionValidation;
use Sigma\MissionEngine\Application\UseCase\RejectMission;
use Sigma\MissionEngine\Application\UseCase\RetrySubtask;
use Sigma\MissionEngine\Application\UseCase\StartSubtaskExecution;
use Sigma\MissionEngine\Application\UseCase\ValidateSubtask;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\MissionStatus;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Tests\Application\Fake\InMemoryMissionRepository;

final class MissionApplicationFlowTest extends TestCase
{
    private InMemoryMissionRepository $missions;
    private InMemoryEventBus $eventBus;
    private \DateTimeImmutable $now;
    private TenantId $tenantId;
    private CorrelationId $correlationId;
    private Actor $actor;

    /** @var list<string> */
    private array $published = [];

    protected function setUp(): void
    {
        $this->missions = new InMemoryMissionRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $this->tenantId = TenantId::generate();
        $this->correlationId = CorrelationId::generate();
        $this->actor = new Actor(ActorType::User, 'felipe');

        $this->published = [];
        foreach (
            [
                'mission.created', 'subtasks.created', 'mission.approval_requested', 'mission.approved',
                'mission.rejected', 'mission.started', 'subtask.retried', 'mission.failed',
                'mission.compensation_started', 'subtask.compensated', 'mission.compensation_finished',
                'mission.finished', 'mission.cancelled',
            ] as $eventName
        ) {
            $this->eventBus->subscribe($eventName, function (array $payload) use ($eventName): void {
                $this->published[] = $eventName;
            });
        }
    }

    private function onePlan(int $requiredAutonomyLevel = 0): Plan
    {
        return new Plan(
            [new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', $requiredAutonomyLevel)],
            PlanSource::Manual,
        );
    }

    public function test_full_happy_path_publishes_every_event_in_order(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(), $this->actor, 1, $this->now);

        $advance = new AdvanceMissionToNextSubtask($this->missions, $this->eventBus);
        $mission = $advance->execute($mission->id(), $this->now);
        $subtaskId = $mission->subtasks()[0]->id();

        (new AssignSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        (new StartSubtaskExecution($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        (new RetrySubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, 'timeout', $this->now);
        (new ValidateSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, ['ok' => true]);
        (new BeginMissionValidation($this->missions))->execute($mission->id(), $this->now);
        $mission = (new PassMissionValidation($this->missions, $this->eventBus))->execute($mission->id(), $this->now);

        self::assertSame(MissionStatus::Completed, $mission->status());
        self::assertSame(
            ['mission.created', 'subtasks.created', 'mission.started', 'subtask.retried', 'mission.finished'],
            $this->published,
        );

        $reloaded = (new GetMission($this->missions))->execute($mission->id());
        self::assertNotNull($reloaded);
        self::assertSame(MissionStatus::Completed, $reloaded->status());
    }

    public function test_approval_flow_publishes_approval_requested_then_approved(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(2), $this->actor, 1, $this->now);

        $advance = new AdvanceMissionToNextSubtask($this->missions, $this->eventBus);
        $mission = $advance->execute($mission->id(), $this->now);
        self::assertSame(MissionStatus::PendingApproval, $mission->status());

        $mission = (new ApproveMission($this->missions, $this->eventBus))->execute($mission->id(), 'felipe', $this->now);

        self::assertSame(MissionStatus::InProgress, $mission->status());
        self::assertSame(
            ['mission.created', 'subtasks.created', 'mission.approval_requested', 'mission.approved'],
            $this->published,
        );
    }

    public function test_reject_flow_cancels_the_mission(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(2), $this->actor, 1, $this->now);
        $mission = (new AdvanceMissionToNextSubtask($this->missions, $this->eventBus))->execute($mission->id(), $this->now);

        $mission = (new RejectMission($this->missions, $this->eventBus))->execute($mission->id(), 'felipe', $this->now);

        self::assertSame(MissionStatus::Cancelled, $mission->status());
        self::assertContains('mission.rejected', $this->published);
    }

    public function test_fail_and_compensate_flow(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(), $this->actor, 1, $this->now);
        $mission = (new AdvanceMissionToNextSubtask($this->missions, $this->eventBus))->execute($mission->id(), $this->now);
        $subtaskId = $mission->subtasks()[0]->id();

        (new AssignSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        (new StartSubtaskExecution($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        $mission = (new FailSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, true, $this->now);
        self::assertSame(MissionStatus::Compensating, $mission->status());

        $mission = (new CompensateSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, 'Cancelou o pedido', true, $this->now);

        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertSame(
            ['mission.created', 'subtasks.created', 'mission.started', 'mission.compensation_started', 'subtask.compensated', 'mission.compensation_finished'],
            $this->published,
        );
    }

    public function test_fail_validation_flow(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(), $this->actor, 1, $this->now);
        $mission = (new AdvanceMissionToNextSubtask($this->missions, $this->eventBus))->execute($mission->id(), $this->now);
        $subtaskId = $mission->subtasks()[0]->id();

        (new AssignSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        (new StartSubtaskExecution($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId);
        (new ValidateSubtask($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, 'done');
        (new BeginMissionValidation($this->missions))->execute($mission->id(), $this->now);

        $mission = (new FailMissionValidation($this->missions, $this->eventBus))->execute($mission->id(), $subtaskId, false, $this->now);

        self::assertSame(MissionStatus::Failed, $mission->status());
        self::assertContains('mission.failed', $this->published);
    }

    public function test_cancel_mission_use_case(): void
    {
        $create = new CreateMission($this->missions, $this->eventBus);
        $mission = $create->execute($this->tenantId, null, $this->correlationId, null, 'Fechar negócio', $this->onePlan(), $this->actor, 1, $this->now);

        $mission = (new CancelMission($this->missions, $this->eventBus))->execute($mission->id(), 'Cliente desistiu', $this->now);

        self::assertSame(MissionStatus::Cancelled, $mission->status());
        self::assertContains('mission.cancelled', $this->published);
    }

    public function test_get_mission_returns_null_when_absent(): void
    {
        $result = (new GetMission($this->missions))->execute(MissionId::generate());

        self::assertNull($result);
    }

    public function test_mutating_a_missing_mission_throws_not_found(): void
    {
        $missingId = MissionId::generate();

        $this->expectException(SigmaException::class);
        $this->expectExceptionCode(0);
        (new AdvanceMissionToNextSubtask($this->missions, $this->eventBus))->execute($missingId, $this->now);
    }

    public function test_approving_a_missing_mission_throws_not_found(): void
    {
        $missingId = MissionId::generate();

        $this->expectException(SigmaException::class);
        (new ApproveMission($this->missions, $this->eventBus))->execute($missingId, 'felipe', $this->now);
    }
}
