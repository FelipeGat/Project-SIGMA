<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\PlannerEngine\Application\UseCase\PlanFromIntent;
use Sigma\PlannerEngine\Domain\Actor;
use Sigma\PlannerEngine\Domain\ActorType;
use Sigma\PlannerEngine\Domain\CorrelationId;
use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\IntentId;
use Sigma\PlannerEngine\Domain\IntentKind;
use Sigma\PlannerEngine\Domain\Plan;
use Sigma\PlannerEngine\Domain\PlanTemplateRegistry;
use Sigma\PlannerEngine\Domain\Planner;
use Sigma\PlannerEngine\Domain\PlanningFailure;
use Sigma\PlannerEngine\Domain\TenantId;
use Sigma\PlannerEngine\Domain\WorkspaceId;

final class PlanFromIntentTest extends TestCase
{
    private InMemoryEventBus $eventBus;
    private PlanFromIntent $useCase;

    protected function setUp(): void
    {
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $this->eventBus);
    }

    /** @param array<string, scalar> $parameters */
    private function intent(IntentKind $kind, array $parameters): Intent
    {
        return new Intent(
            IntentId::generate(),
            CorrelationId::generate(),
            TenantId::generate(),
            WorkspaceId::generate(),
            'Iniciar a implantação do AlfaGym para a Sea Master',
            $kind,
            $parameters,
            new Actor(ActorType::User, 'user-1'),
            0,
        );
    }

    public function test_a_successful_plan_publishes_mission_planned(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$published): void {
            $published[] = $p;
        });

        $outcome = $this->useCase->execute($this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']));

        self::assertInstanceOf(Plan::class, $outcome);
        self::assertCount(1, $published);
        self::assertSame('planner', $published[0]['plan']['source']);
        self::assertNotEmpty($published[0]['plan']['subtaskCandidates']);
    }

    public function test_a_failure_publishes_planning_failed_and_no_mission_planned(): void
    {
        $planned = [];
        $failed = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$planned): void {
            $planned[] = $p;
        });
        $this->eventBus->subscribe('planning.failed', static function (array $p) use (&$failed): void {
            $failed[] = $p;
        });

        $outcome = $this->useCase->execute($this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1']));

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertCount(0, $planned);
        self::assertCount(1, $failed);
        self::assertSame('missing_required_parameter', $failed[0]['reason']);
    }

    /**
     * O `correlationId` precisa atravessar o Planner sem mudar — é o
     * que mantém a rastreabilidade fim-a-fim até o Mission Engine, que
     * o exige em `Mission::create()`.
     */
    public function test_the_correlation_id_survives_into_both_payloads(): void
    {
        $payloads = [];
        foreach (['mission.planned', 'planning.failed'] as $event) {
            $this->eventBus->subscribe($event, static function (array $p) use (&$payloads): void {
                $payloads[] = $p;
            });
        }

        $ok = $this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']);
        $ko = $this->intent(IntentKind::NovaImplantacao, []);

        $this->useCase->execute($ok);
        $this->useCase->execute($ko);

        self::assertCount(2, $payloads);
        self::assertSame($ok->correlationId->toString(), $payloads[0]['correlationId']);
        self::assertSame($ko->correlationId->toString(), $payloads[1]['correlationId']);
    }

    public function test_the_payload_carries_everything_the_mission_engine_needs(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$published): void {
            $published[] = $p;
        });

        $intent = $this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']);
        $this->useCase->execute($intent);

        // Exatamente os campos que `CreateMission::execute()` exige —
        // um consumidor não pode precisar buscar nada em outro lugar.
        foreach (['intentId', 'correlationId', 'tenantId', 'workspaceId', 'objective', 'autonomyCeiling', 'actor', 'plan'] as $key) {
            self::assertArrayHasKey($key, $published[0], sprintf('payload sem "%s".', $key));
        }

        self::assertSame(['type', 'id'], array_keys($published[0]['actor']));
        self::assertSame(
            ['description', 'candidateAgent', 'candidateCapability', 'requiredAutonomyLevel'],
            array_keys($published[0]['plan']['subtaskCandidates'][0]),
        );
    }
}
