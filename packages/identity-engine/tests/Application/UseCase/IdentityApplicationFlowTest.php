<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\UseCase;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\AssignRole;
use Sigma\IdentityEngine\Application\UseCase\GrantPermission;
use Sigma\IdentityEngine\Application\UseCase\Logout;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Application\UseCase\RevokePermission;
use Sigma\IdentityEngine\Application\UseCase\RevokeRole;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\IdentityEngine\Infrastructure\Security\Argon2idCredentialProvider;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryCompanyRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryCredentialRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryIdentityRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryRoleAssignmentRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryRoleRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemorySessionRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryTeamRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryTenantRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryUserRepository;
use Sigma\IdentityEngine\Tests\Application\Fake\InMemoryWorkspaceRepository;
use Sigma\Kernel\InMemoryEventBus;

/**
 * Application testado com implementações em memória das interfaces de
 * repositório (ver "Testes Automatizados" da Proposal da Release 3B) —
 * sem MariaDB, rápido, isolado. A cobertura contra MariaDB real fica
 * para os testes de Infrastructure/Pdo.
 */
final class IdentityApplicationFlowTest extends TestCase
{
    private InMemoryTenantRepository $tenants;
    private InMemoryCompanyRepository $companies;
    private InMemoryWorkspaceRepository $workspaces;
    private InMemoryUserRepository $users;
    private InMemoryCredentialRepository $credentials;
    private InMemoryIdentityRepository $identities;
    private InMemoryTeamRepository $teams;
    private InMemoryRoleRepository $roles;
    private InMemoryRoleAssignmentRepository $roleAssignments;
    private InMemorySessionRepository $sessions;
    private Argon2idCredentialProvider $credentialProvider;
    private InMemoryEventBus $eventBus;

    private Tenant $tenant;
    private Company $company;
    private Workspace $workspace;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->tenants = new InMemoryTenantRepository();
        $this->companies = new InMemoryCompanyRepository();
        $this->workspaces = new InMemoryWorkspaceRepository();
        $this->users = new InMemoryUserRepository();
        $this->credentials = new InMemoryCredentialRepository();
        $this->identities = new InMemoryIdentityRepository();
        $this->teams = new InMemoryTeamRepository();
        $this->roles = new InMemoryRoleRepository();
        $this->roleAssignments = new InMemoryRoleAssignmentRepository();
        $this->sessions = new InMemorySessionRepository();
        $this->credentialProvider = new Argon2idCredentialProvider();
        $this->eventBus = new InMemoryEventBus();

        $this->tenant = new Tenant(TenantId::generate(), 'Alfa Soluções');
        $this->tenants->save($this->tenant);
        $this->company = new Company(CompanyId::generate(), $this->tenant->id(), 'GW');
        $this->companies->save($this->company);
        $this->workspace = new Workspace(WorkspaceId::generate(), $this->company->id(), 'Cliente Brenno');
        $this->workspaces->save($this->workspace);
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_the_full_flow_register_login_select_workspace_resolve_context(): void
    {
        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);
        $identity = $register->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial', autonomyCapabilities: ['CanApproveBudget' => true]);
        $this->roles->save($role);
        (new AssignRole($this->roles, $this->roleAssignments, $this->eventBus))
            ->execute($role->id(), SubjectType::User, $identity->user()->id(), Scope::workspace($this->workspace->id()));
        (new GrantPermission($this->roles, $this->eventBus))->execute($role->id(), 'mission.create');

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);
        $session = $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-forte-123', $this->now);

        $selectWorkspace = new SelectWorkspace($this->identities, $this->workspaces, $this->sessions, $this->eventBus);
        $session = $selectWorkspace->execute($session->id(), $this->workspace->id(), $this->now);

        $resolveContext = new ResolveContext($this->identities, $this->workspaces, $this->companies, $this->teams, $this->roleAssignments, $this->sessions);
        $context = $resolveContext->execute($session->id(), $this->now);

        self::assertTrue($context->hasPermission('mission.create'));
        self::assertTrue($context->canAutonomously('CanApproveBudget'));
    }

    public function test_authenticate_with_wrong_password_is_rejected(): void
    {
        (new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus))
            ->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);

        $this->expectException(SigmaException::class);

        $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-errada', $this->now);
    }

    public function test_authenticate_with_unknown_email_is_rejected_with_the_same_error_as_wrong_password(): void
    {
        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);

        try {
            $authenticate->execute($this->tenant->id(), 'nao-existe@alfa.com', 'qualquer-coisa', $this->now);
            self::fail('Esperava SigmaException.');
        } catch (SigmaException $exception) {
            self::assertSame('identity.invalid_credentials', $exception->errorCode());
        }
    }

    public function test_register_identity_rejects_a_tenant_that_does_not_exist(): void
    {
        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);

        $this->expectException(SigmaException::class);

        $register->execute(TenantId::generate(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');
    }

    public function test_logout_removes_the_session_and_a_second_resolve_context_fails(): void
    {
        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);
        $register->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);
        $session = $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-forte-123', $this->now);

        (new Logout($this->identities, $this->sessions, $this->eventBus))->execute($session->id());

        $resolveContext = new ResolveContext($this->identities, $this->workspaces, $this->companies, $this->teams, $this->roleAssignments, $this->sessions);

        $this->expectException(SigmaException::class);

        $resolveContext->execute($session->id(), $this->now);
    }

    public function test_revoke_role_removes_the_permission_from_a_future_context(): void
    {
        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);
        $identity = $register->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial');
        $this->roles->save($role);
        (new GrantPermission($this->roles, $this->eventBus))->execute($role->id(), 'mission.create');
        $scope = Scope::workspace($this->workspace->id());
        (new AssignRole($this->roles, $this->roleAssignments, $this->eventBus))
            ->execute($role->id(), SubjectType::User, $identity->user()->id(), $scope);

        (new RevokeRole($this->roleAssignments, $this->eventBus))
            ->execute($role->id(), SubjectType::User, $identity->user()->id(), $scope);

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);
        $session = $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-forte-123', $this->now);
        $session = (new SelectWorkspace($this->identities, $this->workspaces, $this->sessions, $this->eventBus))
            ->execute($session->id(), $this->workspace->id(), $this->now);

        $context = (new ResolveContext($this->identities, $this->workspaces, $this->companies, $this->teams, $this->roleAssignments, $this->sessions))
            ->execute($session->id(), $this->now);

        self::assertFalse($context->hasPermission('mission.create'));
    }

    public function test_revoke_permission_removes_it_from_a_future_context(): void
    {
        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);
        $identity = $register->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial');
        $this->roles->save($role);
        (new GrantPermission($this->roles, $this->eventBus))->execute($role->id(), 'mission.create');
        (new AssignRole($this->roles, $this->roleAssignments, $this->eventBus))
            ->execute($role->id(), SubjectType::User, $identity->user()->id(), Scope::workspace($this->workspace->id()));

        (new RevokePermission($this->roles, $this->eventBus))->execute($role->id(), 'mission.create');

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);
        $session = $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-forte-123', $this->now);
        $session = (new SelectWorkspace($this->identities, $this->workspaces, $this->sessions, $this->eventBus))
            ->execute($session->id(), $this->workspace->id(), $this->now);

        $context = (new ResolveContext($this->identities, $this->workspaces, $this->companies, $this->teams, $this->roleAssignments, $this->sessions))
            ->execute($session->id(), $this->now);

        self::assertFalse($context->hasPermission('mission.create'));
    }

    public function test_domain_events_are_published_on_the_event_bus_during_the_flow(): void
    {
        $published = [];
        $this->eventBus->subscribe('identity.created', function (array $payload) use (&$published): void {
            $published[] = 'identity.created';
        });
        $this->eventBus->subscribe('session.started', function (array $payload) use (&$published): void {
            $published[] = 'session.started';
        });

        $register = new RegisterIdentity($this->tenants, $this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->eventBus);
        $register->execute($this->tenant->id(), 'Felipe', 'felipe@alfa.com', 'senha-forte-123');

        $authenticate = new Authenticate($this->users, $this->credentials, $this->identities, $this->credentialProvider, $this->sessions, $this->eventBus);
        $authenticate->execute($this->tenant->id(), 'felipe@alfa.com', 'senha-forte-123', $this->now);

        self::assertSame(['identity.created', 'session.started'], $published);
    }
}
