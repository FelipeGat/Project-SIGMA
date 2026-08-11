<?php

declare(strict_types=1);

namespace Sigma\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Auth\IdentityBootstrapper;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\CreateCompany;
use Sigma\IdentityEngine\Application\UseCase\CreateTenant;
use Sigma\IdentityEngine\Application\UseCase\CreateWorkspace;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoCompanyRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoCredentialRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoIdentityRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoSessionRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoTenantRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoUserRepository;
use Sigma\IdentityEngine\Infrastructure\Pdo\PdoWorkspaceRepository;
use Sigma\IdentityEngine\Infrastructure\Security\Argon2idCredentialProvider;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\InMemoryEventBus;

/**
 * O bootstrap de Identity é o único caminho de produção que cria
 * Tenant/Company/Workspace — até a Release 5.5 essas três entidades só
 * nasciam em fixtures de teste, e `RegisterIdentity` exigia um Tenant
 * que nada sabia criar (ver Decision Log 5.5).
 */
final class IdentityBootstrapperTest extends TestCase
{
    private \PDO $pdo;
    private IContainer $container;

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
        $sessions = new PdoSessionRepository($this->pdo);
        $credentialProvider = new Argon2idCredentialProvider();
        $eventBus = new InMemoryEventBus();

        $container = new Container();
        $container->bind(CreateTenant::class, new CreateTenant($tenants));
        $container->bind(CreateCompany::class, new CreateCompany($tenants, $companies));
        $container->bind(CreateWorkspace::class, new CreateWorkspace($companies, $workspaces));
        $container->bind(RegisterIdentity::class, new RegisterIdentity($tenants, $users, $credentials, $identities, $credentialProvider, $eventBus));
        $container->bind(Authenticate::class, new Authenticate($users, $credentials, $identities, $credentialProvider, $sessions, $eventBus));
        $container->bind(SelectWorkspace::class, new SelectWorkspace($identities, $workspaces, $sessions, $eventBus));

        $this->container = $container;
    }

    public function test_creates_the_full_hierarchy_and_returns_every_id(): void
    {
        $result = (new IdentityBootstrapper($this->container))
            ->execute('Alfa Soluções', 'Alfa Tecnologia', 'Operação', 'Rossini', 'rossini@alfa.com', 'senha-forte-123');

        self::assertSame(
            ['tenantId', 'companyId', 'workspaceId', 'identityId', 'userId'],
            array_keys($result),
        );

        foreach ($result as $key => $id) {
            self::assertNotSame('', $id, sprintf('"%s" não pode voltar vazio.', $key));
        }

        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM tenants')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM workspaces')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
    }

    /**
     * O critério real do bootstrap não é "gravou linhas", e sim "existe
     * alguém capaz de logar e selecionar Workspace" — o primeiro passo
     * do caminho ponta a ponta da Release 5.5.
     */
    public function test_the_created_user_can_authenticate_and_select_the_created_workspace(): void
    {
        $result = (new IdentityBootstrapper($this->container))
            ->execute('Alfa Soluções', 'Alfa Tecnologia', 'Operação', 'Rossini', 'rossini@alfa.com', 'senha-forte-123');

        /** @var Authenticate $authenticate */
        $authenticate = $this->container->get(Authenticate::class);
        $session = $authenticate->execute(
            \Sigma\IdentityEngine\Domain\TenantId::fromString($result['tenantId']),
            'rossini@alfa.com',
            'senha-forte-123',
            new \DateTimeImmutable(),
        );

        /** @var SelectWorkspace $selectWorkspace */
        $selectWorkspace = $this->container->get(SelectWorkspace::class);
        $session = $selectWorkspace->execute($session->id(), WorkspaceId::fromString($result['workspaceId']), new \DateTimeImmutable());

        self::assertSame($result['workspaceId'], $session->workspaceId()?->toString());
    }

    public function test_company_creation_rejects_an_unknown_tenant(): void
    {
        /** @var CreateCompany $createCompany */
        $createCompany = $this->container->get(CreateCompany::class);

        $this->expectException(SigmaException::class);
        $this->expectExceptionMessage('Tenant não encontrado.');

        $createCompany->execute(\Sigma\IdentityEngine\Domain\TenantId::generate(), 'Empresa Fantasma');
    }

    public function test_workspace_creation_rejects_an_unknown_company(): void
    {
        /** @var CreateWorkspace $createWorkspace */
        $createWorkspace = $this->container->get(CreateWorkspace::class);

        $this->expectException(SigmaException::class);
        $this->expectExceptionMessage('Company não encontrada.');

        $createWorkspace->execute(\Sigma\IdentityEngine\Domain\CompanyId::generate(), 'Workspace Fantasma');
    }
}
