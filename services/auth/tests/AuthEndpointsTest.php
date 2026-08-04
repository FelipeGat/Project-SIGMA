<?php

declare(strict_types=1);

namespace Sigma\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Auth\AuthEndpoints;
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
use Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\IdentityEngine\Infrastructure\Security\Argon2idCredentialProvider;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\InMemoryEventBus;

/**
 * Testado com um Container montado à mão sobre uma MariaDB real
 * (pulado se inalcançável) — HTTP real via `php -S` foi validado
 * manualmente durante a Implementation desta Release (ver Decision
 * Log): login com credencial válida chegou até a publicação do evento
 * de domínio, falhando de forma limpa (500, Envelope válido, nunca
 * stack trace) quando o Redis do ambiente não estava alcançável — o
 * mesmo achado documentado aqui via `InMemoryEventBus`, que é
 * exatamente o mecanismo de entrega local que RedisEventBus compõe
 * (ADR-0057), tornando este teste representativo do caminho real.
 */
final class AuthEndpointsTest extends TestCase
{
    private \PDO $pdo;
    private AuthEndpoints $endpoints;
    private Tenant $tenant;
    private Workspace $workspace;

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
            self::markTestSkipped('MariaDB não alcançável para testes de services/auth: ' . $exception->getMessage());
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
        $eventBus = new InMemoryEventBus();

        $this->tenant = new Tenant(TenantId::generate(), 'Alfa Soluções');
        $tenants->save($this->tenant);
        $company = new Company(CompanyId::generate(), $this->tenant->id(), 'GW');
        $companies->save($company);
        $this->workspace = new Workspace(WorkspaceId::generate(), $company->id(), 'Cliente Brenno');
        $workspaces->save($this->workspace);

        $container = new Container();
        $container->bind(IEventBus::class, $eventBus);
        $container->bind(RegisterIdentity::class, new RegisterIdentity($tenants, $users, $credentials, $identities, $credentialProvider, $eventBus));
        $container->bind(Authenticate::class, new Authenticate($users, $credentials, $identities, $credentialProvider, $sessions, $eventBus));
        $container->bind(SelectWorkspace::class, new SelectWorkspace($identities, $workspaces, $sessions, $eventBus));
        $container->bind(ResolveContext::class, new ResolveContext($identities, $workspaces, $companies, $teams, $roleAssignments, $sessions));

        $this->endpoints = new AuthEndpoints($container);

        (new RegisterIdentity($tenants, $users, $credentials, $identities, $credentialProvider, $eventBus))
            ->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');
    }

    public function test_context_for_an_unknown_session_returns_404(): void
    {
        [$status, $body] = $this->endpoints->context('sessao-que-nao-existe');

        self::assertSame(404, $status);
        self::assertSame('identity.session_not_found', $body['error']['code']);
        self::assertFalse($body['success']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        [$status, $body] = $this->endpoints->login([
            'tenantId' => $this->tenant->id()->toString(),
            'email' => 'felipe@alfa.com',
            'password' => 'senha-errada',
        ]);

        self::assertSame(401, $status);
        self::assertSame('identity.invalid_credentials', $body['error']['code']);
    }

    public function test_full_flow_login_select_workspace_resolve_context(): void
    {
        [$loginStatus, $loginBody] = $this->endpoints->login([
            'tenantId' => $this->tenant->id()->toString(),
            'email' => 'felipe@alfa.com',
            'password' => 'senha-forte-123',
        ]);
        self::assertSame(200, $loginStatus);
        $sessionId = $loginBody['data']['sessionId'];

        [$selectStatus] = $this->endpoints->selectWorkspace($sessionId, ['workspaceId' => $this->workspace->id()->toString()]);
        self::assertSame(200, $selectStatus);

        [$contextStatus, $contextBody] = $this->endpoints->context($sessionId);
        self::assertSame(200, $contextStatus);
        self::assertSame($this->workspace->id()->toString(), $contextBody['data']['workspaceId']);
    }

    public function test_every_response_follows_the_envelope_format(): void
    {
        [, $body] = $this->endpoints->context('sessao-que-nao-existe');

        self::assertSame('1.0', $body['protocolVersion']);
        self::assertArrayHasKey('correlationId', $body);
        self::assertArrayHasKey('audit', $body);
    }
}
