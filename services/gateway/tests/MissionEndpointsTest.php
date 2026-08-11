<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Gateway\MissionEndpoints;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner as IdentityMigrationRunner;
use Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema as CreateIdentitySchema;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoCompanyRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoCredentialRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoIdentityRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoRoleAssignmentRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoRoleRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoSessionRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoTeamRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoTenantRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoUserRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoWorkspaceRepository;
use Sigma\IdentityEngine\Infrastructure\Security\Argon2idCredentialProvider;
use Sigma\Kernel\Container;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MissionEngine\Application\UseCase\CreateMission;
use Sigma\MissionEngine\Application\UseCase\GetMission;
use Sigma\MissionEngine\Infrastructure\Migration\MigrationRunner as MissionMigrationRunner;
use Sigma\MissionEngine\Infrastructure\Migration\Migrations\CreateSchema as CreateMissionSchema;
use Sigma\MissionEngine\Infrastructure\Pdo\PdoMissionRepository;

/**
 * As duas primeiras rotas de domínio do SIGMA, contra MariaDB real
 * (pulado se inalcançável) — Identity e Mission no mesmo banco, como
 * em produção (`docker-compose.yml` reaproveita `sigma_identity` para
 * os três Engines: schemas novos, não bancos novos).
 */
final class MissionEndpointsTest extends TestCase
{
    private \PDO $pdo;
    private MissionEndpoints $endpoints;
    private InMemoryEventBus $eventBus;
    private string $sessionToken;
    private TenantId $tenantId;

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
            self::markTestSkipped('MariaDB não alcançável para testes de services/gateway: ' . $exception->getMessage());
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        (new IdentityMigrationRunner($this->pdo))->run([new CreateIdentitySchema()]);
        (new MissionMigrationRunner($this->pdo))->run([new CreateMissionSchema()]);

        $tenants = new PdoTenantRepository($this->pdo);
        $companies = new PdoCompanyRepository($this->pdo);
        $workspaces = new PdoWorkspaceRepository($this->pdo);
        $users = new PdoUserRepository($this->pdo);
        $credentials = new PdoCredentialRepository($this->pdo);
        $identities = new PdoIdentityRepository($this->pdo, $users, $tenants);
        $teams = new PdoTeamRepository($this->pdo);
        $roles = new PdoRoleRepository($this->pdo);
        $roleAssignments = new PdoRoleAssignmentRepository($this->pdo, $roles);
        $sessions = new PdoSessionRepository($this->pdo);
        $credentialProvider = new Argon2idCredentialProvider();
        $this->eventBus = new InMemoryEventBus();

        $tenant = new Tenant(TenantId::generate(), 'Alfa Soluções');
        $tenants->save($tenant);
        $this->tenantId = $tenant->id();
        $company = new Company(CompanyId::generate(), $tenant->id(), 'Alfa Tecnologia');
        $companies->save($company);
        $workspace = new Workspace(WorkspaceId::generate(), $company->id(), 'Operação');
        $workspaces->save($workspace);

        $container = new Container();
        $container->bind(ResolveContext::class, new ResolveContext($identities, $workspaces, $companies, $teams, $roleAssignments, $sessions));
        $missions = new PdoMissionRepository($this->pdo);
        $container->bind(CreateMission::class, new CreateMission($missions, $this->eventBus));
        $container->bind(GetMission::class, new GetMission($missions));

        (new RegisterIdentity($tenants, $users, $credentials, $identities, $credentialProvider, $this->eventBus))
            ->execute($tenant->id(), 'Rossini', 'rossini@alfa.com', 'senha-forte-123');

        $session = (new Authenticate($users, $credentials, $identities, $credentialProvider, $sessions, $this->eventBus))
            ->execute($tenant->id(), 'rossini@alfa.com', 'senha-forte-123', new \DateTimeImmutable());
        $session = (new SelectWorkspace($identities, $workspaces, $sessions, $this->eventBus))
            ->execute($session->id(), $workspace->id(), new \DateTimeImmutable());

        $this->sessionToken = $session->id()->toString();
        $this->endpoints = new MissionEndpoints($container);
    }

    /** @return array<string, mixed> */
    private function validBody(): array
    {
        return [
            'objective' => 'O orçamento da Sea Master precisa refletir as decisões da reunião',
            'autonomyCeiling' => 2,
            'plan' => [
                'subtaskCandidates' => [
                    ['description' => 'Levantar as decisões registradas na reunião', 'requiredAutonomyLevel' => 1],
                    ['description' => 'Atualizar o orçamento no Gestor.Alfa', 'candidateCapability' => 'gestor.budget.update', 'requiredAutonomyLevel' => 2],
                ],
            ],
        ];
    }

    public function test_creates_a_mission_and_returns_201_with_the_envelope(): void
    {
        [$status, $body] = $this->endpoints->create($this->sessionToken, $this->validBody());

        self::assertSame(201, $status);
        self::assertTrue($body['success']);
        self::assertSame('created', $body['data']['status']);
        self::assertSame($this->tenantId->toString(), $body['data']['tenantId']);
        self::assertCount(2, $body['data']['plan']['subtaskCandidates']);
        self::assertSame('manual', $body['data']['plan']['source']);
    }

    public function test_the_created_mission_is_persisted_and_readable_back(): void
    {
        [, $created] = $this->endpoints->create($this->sessionToken, $this->validBody());
        $missionId = $created['data']['missionId'];

        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM missions')->fetchColumn());

        [$status, $body] = $this->endpoints->get($this->sessionToken, $missionId);

        self::assertSame(200, $status);
        self::assertSame($missionId, $body['data']['missionId']);
        self::assertSame('O orçamento da Sea Master precisa refletir as decisões da reunião', $body['data']['objective']);
    }

    public function test_creating_a_mission_publishes_the_domain_event(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.created', static function (array $payload) use (&$published): void {
            $published[] = $payload;
        });

        $this->endpoints->create($this->sessionToken, $this->validBody());

        self::assertCount(1, $published);
    }

    /**
     * `tenantId`/`workspaceId` vêm da Session, nunca do corpo — sem
     * isto, qualquer requisitante criaria Mission no escopo de outro
     * Tenant só mudando o JSON enviado.
     */
    public function test_tenant_and_workspace_come_from_the_session_not_from_the_body(): void
    {
        $body = $this->validBody();
        $body['tenantId'] = TenantId::generate()->toString();
        $body['workspaceId'] = WorkspaceId::generate()->toString();

        [, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame($this->tenantId->toString(), $response['data']['tenantId']);
    }

    public function test_creating_without_a_session_token_returns_401(): void
    {
        [$status, $body] = $this->endpoints->create(null, $this->validBody());

        self::assertSame(401, $status);
        self::assertFalse($body['success']);
    }

    public function test_creating_with_an_unknown_session_token_returns_401(): void
    {
        [$status] = $this->endpoints->create(\Sigma\IdentityEngine\Domain\SessionId::generate()->toString(), $this->validBody());

        self::assertSame(401, $status);
    }

    public function test_creating_without_an_objective_returns_400(): void
    {
        $body = $this->validBody();
        unset($body['objective']);

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(400, $status);
        self::assertSame('mission.missing_objective', $response['error']['code']);
    }

    public function test_creating_without_a_plan_returns_400(): void
    {
        $body = $this->validBody();
        unset($body['plan']);

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(400, $status);
        self::assertSame('mission.missing_plan', $response['error']['code']);
    }

    public function test_creating_with_an_out_of_range_autonomy_ceiling_returns_400(): void
    {
        $body = $this->validBody();
        $body['autonomyCeiling'] = 9;

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(400, $status);
        self::assertSame('mission.invalid_autonomy_ceiling', $response['error']['code']);
    }

    public function test_reading_an_unknown_mission_returns_404(): void
    {
        [$status, $body] = $this->endpoints->get($this->sessionToken, \Sigma\MissionEngine\Domain\MissionId::generate()->toString());

        self::assertSame(404, $status);
        self::assertSame('mission.not_found', $body['error']['code']);
    }

    public function test_reading_without_a_session_token_returns_401(): void
    {
        [$status] = $this->endpoints->get(null, \Sigma\MissionEngine\Domain\MissionId::generate()->toString());

        self::assertSame(401, $status);
    }
}
