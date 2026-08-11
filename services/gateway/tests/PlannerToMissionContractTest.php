<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MissionEngine\Application\UseCase\CreateMission;
use Sigma\MissionEngine\Application\UseCase\CreateMissionFromPlannedEvent;
use Sigma\MissionEngine\Domain\MissionStatus;
use Sigma\MissionEngine\Domain\PlanSource as MissionPlanSource;
use Sigma\MissionEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\MissionEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\MissionEngine\Infrastructure\Pdo\PdoMissionRepository;
use Sigma\PlannerEngine\Application\UseCase\PlanFromIntent;
use Sigma\PlannerEngine\Domain\Actor;
use Sigma\PlannerEngine\Domain\ActorType;
use Sigma\PlannerEngine\Domain\CorrelationId;
use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\IntentId;
use Sigma\PlannerEngine\Domain\IntentKind;
use Sigma\PlannerEngine\Domain\PlanTemplateRegistry;
use Sigma\PlannerEngine\Domain\Planner;
use Sigma\PlannerEngine\Domain\TenantId;
use Sigma\PlannerEngine\Domain\WorkspaceId;

/**
 * A prova prática de [ADR-0096](../../../docs/adr/0096-plan-duplicado-por-bounded-context.md):
 * o payload que o Planner publica é aceito pelo Mission Engine **sem
 * nenhuma adaptação**.
 *
 * As duas representações de `Plan`/`SubtaskCandidate` são código
 * separado, de propósito — e nada no compilador impede que divirjam. É
 * este teste que impede: ele leva o payload real de ponta a ponta, e
 * quebra no instante em que os dois lados deixarem de casar.
 *
 * Vive em `services/gateway` porque é o único lugar do monorepo que
 * enxerga os dois pacotes. Nenhum dos dois Engines depende do outro, e
 * é assim que continua.
 */
final class PlannerToMissionContractTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $host = getenv('IDENTITY_TEST_DB_HOST') ?: '127.0.0.1';
        $port = getenv('IDENTITY_TEST_DB_PORT') ?: '3306';
        $name = getenv('IDENTITY_TEST_DB_NAME') ?: 'sigma_identity_test';
        $user = getenv('IDENTITY_TEST_DB_USER') ?: 'root';
        $password = getenv('IDENTITY_TEST_DB_PASSWORD') ?: '';

        try {
            $this->pdo = new \PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $exception) {
            self::markTestSkipped('MariaDB não alcançável: ' . $exception->getMessage());
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);
    }

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

    public function test_the_planner_payload_creates_a_mission_without_any_adaptation(): void
    {
        $eventBus = new InMemoryEventBus();
        $captured = [];
        $eventBus->subscribe('mission.planned', static function (array $p) use (&$captured): void {
            $captured[] = $p;
        });

        $intent = $this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']);
        (new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $eventBus))->execute($intent);

        self::assertCount(1, $captured, 'O Planner deveria ter publicado mission.planned.');

        $missions = new PdoMissionRepository($this->pdo);
        $createFromEvent = new CreateMissionFromPlannedEvent(new CreateMission($missions, $eventBus));

        // O payload vai direto, exatamente como saiu do Planner.
        $mission = $createFromEvent->execute($captured[0], new \DateTimeImmutable());

        self::assertSame(MissionStatus::Created, $mission->status());
        self::assertSame($intent->objective, $mission->objective());
        self::assertSame(MissionPlanSource::Planner, $mission->plan()->source);
        self::assertCount(
            count($captured[0]['plan']['subtaskCandidates']),
            $mission->plan()->subtaskCandidates,
        );
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM missions')->fetchColumn());
    }

    /**
     * A rastreabilidade fim-a-fim precisa sobreviver à fronteira
     * assíncrona: o `correlationId` que entrou na Intent é o mesmo que
     * a Mission carrega depois de criada.
     */
    public function test_the_correlation_id_survives_from_intent_to_persisted_mission(): void
    {
        $eventBus = new InMemoryEventBus();
        $captured = [];
        $eventBus->subscribe('mission.planned', static function (array $p) use (&$captured): void {
            $captured[] = $p;
        });

        $intent = $this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']);
        (new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $eventBus))->execute($intent);

        $missions = new PdoMissionRepository($this->pdo);
        $mission = (new CreateMissionFromPlannedEvent(new CreateMission($missions, $eventBus)))
            ->execute($captured[0], new \DateTimeImmutable());

        self::assertSame($intent->correlationId->toString(), $mission->correlationId()->toString());

        $reloaded = $missions->find($mission->id());
        self::assertNotNull($reloaded);
        self::assertSame($intent->correlationId->toString(), $reloaded->correlationId()->toString());
    }

    /**
     * Todos os sete templates precisam produzir um payload que o
     * Mission Engine aceite — não só o de nova_implantacao.
     */
    public function test_every_template_produces_a_payload_the_mission_engine_accepts(): void
    {
        $parameters = [
            'nova_reuniao' => ['clientId' => 'c-1'],
            'novo_cliente' => ['razaoSocial' => 'Sea Master Ltda', 'produto' => 'AlfaControl'],
            'novo_orcamento' => ['clientId' => 'c-1', 'escopo' => '3 catracas'],
            'nova_obra' => ['orcamentoId' => 'o-1'],
            'nova_implantacao' => ['clientId' => 'c-1', 'sistema' => 'AlfaGym'],
            'nova_academia' => ['clientId' => 'c-1'],
            'novo_condominio' => ['clientId' => 'c-1'],
        ];

        $missions = new PdoMissionRepository($this->pdo);

        foreach (IntentKind::cases() as $kind) {
            $eventBus = new InMemoryEventBus();
            $captured = [];
            $eventBus->subscribe('mission.planned', static function (array $p) use (&$captured): void {
                $captured[] = $p;
            });

            (new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $eventBus))
                ->execute($this->intent($kind, $parameters[$kind->value]));

            $mission = (new CreateMissionFromPlannedEvent(new CreateMission($missions, $eventBus)))
                ->execute($captured[0], new \DateTimeImmutable());

            self::assertSame(
                MissionStatus::Created,
                $mission->status(),
                sprintf('O payload de "%s" não produziu uma Mission válida.', $kind->value),
            );
        }

        self::assertSame(
            count(IntentKind::cases()),
            (int) $this->pdo->query('SELECT COUNT(*) FROM missions')->fetchColumn(),
        );
    }

    public function test_a_payload_without_correlation_id_is_rejected(): void
    {
        $eventBus = new InMemoryEventBus();
        $captured = [];
        $eventBus->subscribe('mission.planned', static function (array $p) use (&$captured): void {
            $captured[] = $p;
        });

        (new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $eventBus))
            ->execute($this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => 'AlfaGym']));

        $payload = $captured[0];
        unset($payload['correlationId']);

        $this->expectException(\Sigma\Core\SigmaException::class);

        (new CreateMissionFromPlannedEvent(new CreateMission(new PdoMissionRepository($this->pdo), $eventBus)))
            ->execute($payload, new \DateTimeImmutable());
    }
}
