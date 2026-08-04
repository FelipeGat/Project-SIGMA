<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Infrastructure\Pdo;

use PHPUnit\Framework\TestCase;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\Team;
use Sigma\IdentityEngine\Domain\TeamId;
use Sigma\IdentityEngine\Domain\TeamType;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\User;
use Sigma\IdentityEngine\Domain\UserId;
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
use Sigma\IdentityEngine\Tests\Infrastructure\PdoTestConnection;

/**
 * Round-trip de cada repositório contra uma MariaDB real — ver
 * "Testes Automatizados" da Proposal da Release 3B. Pulados
 * automaticamente se nenhuma MariaDB estiver alcançável.
 */
final class PdoRepositoriesTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = PdoTestConnection::connectOrSkip();
    }

    public function test_tenant_round_trips(): void
    {
        $repository = new PdoTenantRepository($this->pdo);
        $tenant = new Tenant(TenantId::generate(), 'Alfa Soluções');

        $repository->save($tenant);
        $found = $repository->find($tenant->id());

        self::assertNotNull($found);
        self::assertSame('Alfa Soluções', $found->name());
    }

    public function test_company_and_workspace_round_trip(): void
    {
        $tenant = $this->persistTenant();
        $companies = new PdoCompanyRepository($this->pdo);
        $company = new Company(CompanyId::generate(), $tenant->id(), 'GW');
        $companies->save($company);

        $workspaces = new PdoWorkspaceRepository($this->pdo);
        $workspace = new Workspace(WorkspaceId::generate(), $company->id(), 'Cliente Brenno');
        $workspaces->save($workspace);

        self::assertSame('GW', $companies->find($company->id())?->name());
        self::assertSame('Cliente Brenno', $workspaces->find($workspace->id())?->name());
    }

    public function test_user_and_credential_round_trip(): void
    {
        $tenant = $this->persistTenant();
        $users = new PdoUserRepository($this->pdo);
        $user = new User(UserId::generate(), $tenant->id(), 'Felipe', 'felipe@alfa.com');
        $users->save($user);

        $credentials = new PdoCredentialRepository($this->pdo);
        $credentials->setPasswordHash($user->id(), 'hash-fake');

        self::assertSame('felipe@alfa.com', $users->findByEmail($tenant->id(), 'felipe@alfa.com')?->email());
        self::assertSame('hash-fake', $credentials->passwordHash($user->id()));
    }

    public function test_identity_round_trips_with_active_state(): void
    {
        $tenant = $this->persistTenant();
        $user = $this->persistUser($tenant);

        $identities = new PdoIdentityRepository($this->pdo, new PdoUserRepository($this->pdo), new PdoTenantRepository($this->pdo));
        $identity = Identity::create($user, $tenant);
        $identity->activate();
        $identities->save($identity);

        $found = $identities->findByUserId($user->id());

        self::assertNotNull($found);
        self::assertTrue($found->isActive());
        self::assertTrue($found->id()->equals($identity->id()));
    }

    public function test_team_round_trips_with_members(): void
    {
        $tenant = $this->persistTenant();
        $company = $this->persistCompany($tenant);
        $user = $this->persistUser($tenant);

        $teams = new PdoTeamRepository($this->pdo);
        $team = new Team(TeamId::generate(), $company->id(), 'Comercial', TeamType::Business);
        $team->addMember($user->id());
        $teams->save($team);

        $found = $teams->find($team->id());

        self::assertNotNull($found);
        self::assertTrue($found->hasMember($user->id()));
        self::assertSame(TeamType::Business, $found->type());
    }

    public function test_role_round_trips_with_permissions_and_autonomy_capabilities(): void
    {
        $tenant = $this->persistTenant();
        $roles = new PdoRoleRepository($this->pdo);
        $role = new Role(
            RoleId::generate(),
            $tenant->id(),
            'Comercial',
            [Permission::fromKey('mission.create')],
            ['CanApproveBudget' => true],
        );
        $roles->save($role);

        $found = $roles->find($role->id());

        self::assertNotNull($found);
        self::assertTrue($found->hasPermission('mission.create'));
        self::assertTrue($found->canAutonomously('CanApproveBudget'));
    }

    public function test_role_assignment_round_trips_and_respects_tenant_filter(): void
    {
        $tenant = $this->persistTenant();
        $user = $this->persistUser($tenant);
        $roles = new PdoRoleRepository($this->pdo);
        $role = new Role(RoleId::generate(), $tenant->id(), 'Comercial', [Permission::fromKey('mission.create')]);
        $roles->save($role);

        $roleAssignments = new PdoRoleAssignmentRepository($this->pdo, $roles);
        $scope = Scope::tenant($tenant->id());
        $assignment = RoleAssignment::assign($role, SubjectType::User, $user->id(), $scope);
        $roleAssignments->save($assignment);

        $found = $roleAssignments->findExact($role->id(), SubjectType::User, $user->id(), $scope);
        self::assertNotNull($found);
        self::assertFalse($found->isRevoked());

        $applicable = $roleAssignments->findForUserAndTeams($tenant->id(), $user->id(), []);
        self::assertCount(1, $applicable);

        $applicableOtherTenant = $roleAssignments->findForUserAndTeams(TenantId::generate(), $user->id(), []);
        self::assertCount(0, $applicableOtherTenant);
    }

    public function test_revoking_a_role_assignment_persists(): void
    {
        $tenant = $this->persistTenant();
        $user = $this->persistUser($tenant);
        $roles = new PdoRoleRepository($this->pdo);
        $role = new Role(RoleId::generate(), $tenant->id(), 'Comercial');
        $roles->save($role);

        $roleAssignments = new PdoRoleAssignmentRepository($this->pdo, $roles);
        $scope = Scope::tenant($tenant->id());
        $assignment = RoleAssignment::assign($role, SubjectType::User, $user->id(), $scope);
        $roleAssignments->save($assignment);

        $assignment->revoke();
        $roleAssignments->save($assignment);

        $found = $roleAssignments->findExact($role->id(), SubjectType::User, $user->id(), $scope);
        self::assertTrue($found->isRevoked());
    }

    public function test_session_round_trips_including_workspace_selection(): void
    {
        $tenant = $this->persistTenant();
        $user = $this->persistUser($tenant);
        $identities = new PdoIdentityRepository($this->pdo, new PdoUserRepository($this->pdo), new PdoTenantRepository($this->pdo));
        $identity = Identity::create($user, $tenant);
        $identity->activate();
        $identities->save($identity);

        $sessions = new PdoSessionRepository($this->pdo);
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $session = $identity->authenticate($now, new \DateInterval('PT8H'));
        $sessions->save($session);

        $workspaceId = WorkspaceId::generate();
        $selected = $session->withWorkspaceSelected($workspaceId);
        $sessions->save($selected);

        $found = $sessions->find($session->id());
        self::assertNotNull($found);
        self::assertTrue($found->workspaceId()->equals($workspaceId));

        $sessions->delete($session->id());
        self::assertNull($sessions->find($session->id()));
    }

    public function test_running_the_migration_twice_is_idempotent(): void
    {
        // setUp() já rodou a migration uma vez; rodar de novo aqui não pode falhar.
        $runner = new \Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner($this->pdo);
        $runner->run([new \Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema()]);

        $this->expectNotToPerformAssertions();
    }

    private function persistTenant(): Tenant
    {
        $tenant = new Tenant(TenantId::generate(), 'Alfa Soluções');
        (new PdoTenantRepository($this->pdo))->save($tenant);

        return $tenant;
    }

    private function persistCompany(Tenant $tenant): Company
    {
        $company = new Company(CompanyId::generate(), $tenant->id(), 'GW');
        (new PdoCompanyRepository($this->pdo))->save($company);

        return $company;
    }

    private function persistUser(Tenant $tenant): User
    {
        $user = new User(UserId::generate(), $tenant->id(), 'Felipe', sprintf('felipe-%s@alfa.com', bin2hex(random_bytes(4))));
        (new PdoUserRepository($this->pdo))->save($user);

        return $user;
    }
}
