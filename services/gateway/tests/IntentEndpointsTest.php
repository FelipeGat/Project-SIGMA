<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Gateway\IntentEndpoints;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema;
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
use Sigma\PlannerEngine\Application\UseCase\PlanFromIntent;
use Sigma\PlannerEngine\Domain\PlanTemplateRegistry;
use Sigma\PlannerEngine\Domain\Planner;

/**
 * `POST /intents` — a rota onde o usuário descreve o **quê** e o SIGMA
 * decide o **como** (Release 6B). Contra MariaDB real, como as demais
 * rotas de domínio.
 */
final class IntentEndpointsTest extends TestCase
{
    private \PDO $pdo;
    private IntentEndpoints $endpoints;
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
            self::markTestSkipped('MariaDB não alcançável: ' . $exception->getMessage());
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);

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
        $container->bind(PlanFromIntent::class, new PlanFromIntent(new Planner(new PlanTemplateRegistry()), $this->eventBus));

        (new RegisterIdentity($tenants, $users, $credentials, $identities, $credentialProvider, $this->eventBus))
            ->execute($tenant->id(), 'Rossini', 'rossini@alfa.com', 'senha-forte-123');
        $session = (new Authenticate($users, $credentials, $identities, $credentialProvider, $sessions, $this->eventBus))
            ->execute($tenant->id(), 'rossini@alfa.com', 'senha-forte-123', new \DateTimeImmutable());
        $session = (new SelectWorkspace($identities, $workspaces, $sessions, $this->eventBus))
            ->execute($session->id(), $workspace->id(), new \DateTimeImmutable());

        $this->sessionToken = $session->id()->toString();
        $this->endpoints = new IntentEndpoints($container);
    }

    /** @return array<string, mixed> */
    private function validBody(): array
    {
        return [
            'objective' => 'Iniciar a implantação do AlfaGym para a Sea Master',
            'kind' => 'nova_implantacao',
            'parameters' => ['clientId' => 'c-1', 'sistema' => 'AlfaGym'],
        ];
    }

    public function test_a_valid_intent_returns_202_and_publishes_mission_planned(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$published): void {
            $published[] = $p;
        });

        [$status, $body] = $this->endpoints->create($this->sessionToken, $this->validBody());

        self::assertSame(202, $status);
        self::assertTrue($body['success']);
        self::assertSame('planned', $body['data']['status']);
        self::assertSame('nova_implantacao', $body['data']['kind']);
        self::assertGreaterThan(0, $body['data']['subtaskCandidates']);
        self::assertCount(1, $published);
    }

    /**
     * O `correlationId` devolvido na resposta é o mesmo que viaja no
     * evento — é o que permite a quem chamou correlacionar a Mission
     * criada assincronamente com a requisição que a originou.
     */
    public function test_the_returned_correlation_id_is_the_one_published(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$published): void {
            $published[] = $p;
        });

        [, $body] = $this->endpoints->create($this->sessionToken, $this->validBody());

        self::assertSame($body['data']['correlationId'], $published[0]['correlationId']);
    }

    /**
     * `tenantId`/`workspaceId` vêm da Session, nunca do corpo — sem
     * isso, um requisitante autenticado planejaria no escopo de outro
     * Tenant só mudando o JSON enviado.
     */
    public function test_tenant_comes_from_the_session_not_from_the_body(): void
    {
        $published = [];
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$published): void {
            $published[] = $p;
        });

        $body = $this->validBody();
        $body['tenantId'] = TenantId::generate()->toString();

        $this->endpoints->create($this->sessionToken, $body);

        self::assertSame($this->tenantId->toString(), $published[0]['tenantId']);
    }

    /**
     * Planejamento impossível responde 422, não 400: o pedido foi
     * entendido e aceito; o sistema é que reconhece não saber
     * executá-lo (ADR-0097).
     */
    public function test_an_unplannable_intent_returns_422_and_publishes_planning_failed(): void
    {
        $failed = [];
        $planned = [];
        $this->eventBus->subscribe('planning.failed', static function (array $p) use (&$failed): void {
            $failed[] = $p;
        });
        $this->eventBus->subscribe('mission.planned', static function (array $p) use (&$planned): void {
            $planned[] = $p;
        });

        $body = $this->validBody();
        unset($body['parameters']['sistema']);

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(422, $status);
        self::assertFalse($response['success']);
        self::assertSame('planning.missing_required_parameter', $response['error']['code']);
        self::assertCount(1, $failed);
        self::assertCount(0, $planned);
    }

    public function test_an_unknown_kind_returns_400_without_publishing_anything(): void
    {
        $seen = [];
        foreach (['mission.planned', 'planning.failed'] as $event) {
            $this->eventBus->subscribe($event, static function (array $p) use (&$seen): void {
                $seen[] = $p;
            });
        }

        $body = $this->validBody();
        $body['kind'] = 'nao_existe';

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        // 400, não 422: um `kind` fora do enum é requisição malformada,
        // não uma lacuna de Playbook — o Planner nem chega a ser
        // chamado, e por isso nada é publicado.
        self::assertSame(400, $status);
        self::assertSame('intent.invalid_kind', $response['error']['code']);
        self::assertCount(0, $seen);
    }

    public function test_missing_objective_returns_400(): void
    {
        $body = $this->validBody();
        unset($body['objective']);

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(400, $status);
        self::assertSame('intent.missing_objective', $response['error']['code']);
    }

    public function test_non_scalar_parameters_are_rejected(): void
    {
        $body = $this->validBody();
        $body['parameters']['sistema'] = ['AlfaGym'];

        [$status, $response] = $this->endpoints->create($this->sessionToken, $body);

        self::assertSame(400, $status);
        self::assertSame('intent.invalid_parameters', $response['error']['code']);
    }

    public function test_without_a_session_token_returns_401(): void
    {
        [$status, $body] = $this->endpoints->create(null, $this->validBody());

        self::assertSame(401, $status);
        self::assertFalse($body['success']);
    }

    public function test_with_an_unknown_session_token_returns_401(): void
    {
        [$status] = $this->endpoints->create(SessionId::generate()->toString(), $this->validBody());

        self::assertSame(401, $status);
    }
}
