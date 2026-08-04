<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Interfaces;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\AssignRole;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\GrantPermission;
use Sigma\IdentityEngine\Application\UseCase\Logout;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Application\UseCase\RevokePermission;
use Sigma\IdentityEngine\Application\UseCase\RevokeRole;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
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
use Sigma\IdentityEngine\Infrastructure\Security\Argon2idPasswordHasher;
use Sigma\Kernel\ConfigurationProvider;
use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;

/**
 * O Module `identity-engine` — o primeiro `IModule` de domínio real do
 * SIGMA (`kind: Engine`, ver ADR-0040: `kind` é metadado, o Kernel não
 * trata este Module de forma especial). `register()` conecta ao banco
 * e monta os casos de uso; `boot()` aplica as migrations — é aqui,
 * não em register(), que o schema precisa estar pronto antes de
 * `ready` (ver Decision Log da Release 3B sobre esta escolha).
 */
final class IdentityEngineModule implements IModule
{
    private const VERSION = '1.0.0';

    private ?\PDO $pdo = null;

    /** @param array<string, string|null> $env Fonte de configuração — getenv() por padrão, injetável para testes. */
    public function __construct(private readonly array $env = [])
    {
    }

    public function name(): string
    {
        return 'identity-engine';
    }

    public function kind(): ModuleKind
    {
        return ModuleKind::Engine;
    }

    public function dependsOn(): array
    {
        return ['kernel', 'event-bus'];
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema(
            required: ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'],
            optional: ['DB_PASSWORD'],
        );
    }

    public function register(IContainer $container): void
    {
        $config = new ConfigurationProvider($this->configSchema(), $this->env, $this->name());

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config->require('DB_HOST'),
            $config->require('DB_PORT'),
            $config->require('DB_NAME'),
        );
        try {
            $this->pdo = new \PDO($dsn, $config->require('DB_USER'), (string) $config->get('DB_PASSWORD', ''), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $exception) {
            // Nunca deixar \PDOException vazar para fora do Module — todo
            // Module do Kernel API só lança SigmaException (achado real ao
            // escrever o teste de Bootstrap desta Release: sem isto, um
            // MariaDB inalcançável quebrava com um erro não tratado em vez
            // de uma falha explícita e identificável).
            throw new SigmaException(
                sprintf('Module "identity-engine": não foi possível conectar ao banco — %s', $exception->getMessage()),
                'identity_engine.database_unreachable',
                $exception,
            );
        }

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
        $passwordHasher = new Argon2idPasswordHasher();

        /** @var IEventBus $eventBus */
        $eventBus = $container->get(IEventBus::class);

        $container->bind(RegisterIdentity::class, new RegisterIdentity($tenants, $users, $credentials, $identities, $passwordHasher, $eventBus));
        $container->bind(Authenticate::class, new Authenticate($users, $credentials, $identities, $passwordHasher, $sessions, $eventBus));
        $container->bind(SelectWorkspace::class, new SelectWorkspace($identities, $workspaces, $sessions, $eventBus));
        $container->bind(ResolveContext::class, new ResolveContext($identities, $workspaces, $companies, $teams, $roleAssignments, $sessions));
        $container->bind(Logout::class, new Logout($identities, $sessions, $eventBus));
        $container->bind(AssignRole::class, new AssignRole($roles, $roleAssignments, $eventBus));
        $container->bind(RevokeRole::class, new RevokeRole($roleAssignments, $eventBus));
        $container->bind(GrantPermission::class, new GrantPermission($roles, $eventBus));
        $container->bind(RevokePermission::class, new RevokePermission($roles, $eventBus));
    }

    public function boot(): void
    {
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name(),
            type: $this->kind(),
            version: self::VERSION,
            status: $this->pdo !== null ? ModuleStatus::Ready : ModuleStatus::Boot,
            capabilities: [
                'RegisterIdentity', 'Authenticate', 'SelectWorkspace', 'ResolveContext',
                'Logout', 'AssignRole', 'RevokeRole', 'GrantPermission', 'RevokePermission',
            ],
            dependencies: $this->dependsOn(),
        );
    }
}
