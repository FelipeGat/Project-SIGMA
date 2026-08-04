<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Event\IdentityActivated;
use Sigma\IdentityEngine\Domain\Event\IdentityCreated;
use Sigma\IdentityEngine\Domain\Event\SessionStarted;
use Sigma\IdentityEngine\Domain\Event\WorkspaceSelected;
use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
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

/**
 * Cobre o fluxo completo de IDENTITY_LIFECYCLE.md, ponta a ponta, em
 * memória — sem nenhuma infraestrutura (Critério de Aceite da Release
 * 3A, ver docs/releases/0003a-identity-domain.md).
 */
final class IdentityTest extends TestCase
{
    private Tenant $tenant;
    private Company $company;
    private Workspace $workspace;
    private User $user;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $tenantId = TenantId::generate();
        $companyId = CompanyId::generate();
        $workspaceId = WorkspaceId::generate();

        $this->tenant = new Tenant($tenantId, 'Alfa Soluções');
        $this->company = new Company($companyId, $tenantId, 'GW');
        $this->workspace = new Workspace($workspaceId, $companyId, 'Cliente Brenno');
        $this->user = new User(UserId::generate(), $tenantId, 'Felipe', 'felipe@alfa.com');
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_the_full_lifecycle_produces_a_context_with_effective_permissions_and_autonomy_via_direct_role_assignment(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $role = new Role(
            RoleId::generate(),
            $this->tenant->id(),
            'Comercial',
            [Permission::fromKey('mission.create')],
            ['CanApproveBudget' => true],
        );
        $assignment = RoleAssignment::assign($role, SubjectType::User, $this->user->id(), Scope::workspace($this->workspace->id()));

        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $session = $identity->selectWorkspace($session, $this->workspace, $this->now);

        $context = $identity->resolveContext($session, $this->workspace, $this->company, [$assignment], [], $this->now);

        self::assertTrue($context->hasPermission('mission.create'));
        self::assertTrue($context->canAutonomously('CanApproveBudget'));
        self::assertFalse($context->canAutonomously('CanDeleteMission'));
        self::assertTrue($context->workspaceId->equals($this->workspace->id()));
        self::assertTrue($context->companyId->equals($this->company->id()));
        self::assertTrue($context->tenantId->equals($this->tenant->id()));
        self::assertTrue($context->identityId->equals($identity->id()));
    }

    public function test_a_role_received_only_via_team_membership_still_resolves(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $team = new Team(TeamId::generate(), $this->company->id(), 'Comercial', TeamType::Business);
        $team->addMember($this->user->id());

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial', [Permission::fromKey('mission.create')]);
        $assignment = RoleAssignment::assign($role, SubjectType::Team, $team->id(), Scope::workspace($this->workspace->id()));

        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $session = $identity->selectWorkspace($session, $this->workspace, $this->now);

        $context = $identity->resolveContext($session, $this->workspace, $this->company, [$assignment], [$team], $this->now);

        self::assertTrue($context->hasPermission('mission.create'));
    }

    public function test_an_assignment_scoped_to_the_tenant_applies_to_any_workspace_within_it(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Admin', [Permission::fromKey('tenant.manage')]);
        $assignment = RoleAssignment::assign($role, SubjectType::User, $this->user->id(), Scope::tenant($this->tenant->id()));

        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $session = $identity->selectWorkspace($session, $this->workspace, $this->now);

        $context = $identity->resolveContext($session, $this->workspace, $this->company, [$assignment], [], $this->now);

        self::assertTrue($context->hasPermission('tenant.manage'));
    }

    public function test_an_assignment_scoped_to_a_different_workspace_does_not_apply(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial', [Permission::fromKey('mission.create')]);
        $assignment = RoleAssignment::assign($role, SubjectType::User, $this->user->id(), Scope::workspace(WorkspaceId::generate()));

        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $session = $identity->selectWorkspace($session, $this->workspace, $this->now);

        $context = $identity->resolveContext($session, $this->workspace, $this->company, [$assignment], [], $this->now);

        self::assertFalse($context->hasPermission('mission.create'));
    }

    public function test_a_revoked_assignment_does_not_apply(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $role = new Role(RoleId::generate(), $this->tenant->id(), 'Comercial', [Permission::fromKey('mission.create')]);
        $assignment = RoleAssignment::assign($role, SubjectType::User, $this->user->id(), Scope::workspace($this->workspace->id()));
        $assignment->revoke();

        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $session = $identity->selectWorkspace($session, $this->workspace, $this->now);

        $context = $identity->resolveContext($session, $this->workspace, $this->company, [$assignment], [], $this->now);

        self::assertFalse($context->hasPermission('mission.create'));
    }

    public function test_an_inactive_identity_cannot_authenticate(): void
    {
        $identity = Identity::create($this->user, $this->tenant);

        $this->expectException(SigmaException::class);

        $identity->authenticate($this->now, new \DateInterval('PT8H'));
    }

    public function test_a_disabled_identity_cannot_authenticate(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();
        $identity->disable('solicitado pelo usuário');

        $this->expectException(SigmaException::class);

        $identity->authenticate($this->now, new \DateInterval('PT8H'));
    }

    public function test_an_expired_session_cannot_select_a_workspace(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();
        $session = $identity->authenticate($this->now, new \DateInterval('PT1H'));

        $this->expectException(SigmaException::class);

        $identity->selectWorkspace($session, $this->workspace, $this->now->modify('+2 hours'));
    }

    public function test_resolve_context_without_workspace_selected_on_the_session_is_rejected(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();
        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));

        $this->expectException(SigmaException::class);

        $identity->resolveContext($session, $this->workspace, $this->company, [], [], $this->now);
    }

    public function test_a_session_from_a_different_identity_is_rejected(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();

        $otherUser = new User(UserId::generate(), $this->tenant->id(), 'Outra Pessoa', 'outra@alfa.com');
        $otherIdentity = Identity::create($otherUser, $this->tenant);
        $otherIdentity->activate();
        $foreignSession = $otherIdentity->authenticate($this->now, new \DateInterval('PT8H'));

        $this->expectException(SigmaException::class);

        $identity->selectWorkspace($foreignSession, $this->workspace, $this->now);
    }

    public function test_the_full_lifecycle_produces_the_expected_sequence_of_domain_events(): void
    {
        $identity = Identity::create($this->user, $this->tenant);
        $identity->activate();
        $session = $identity->authenticate($this->now, new \DateInterval('PT8H'));
        $identity->selectWorkspace($session, $this->workspace, $this->now);

        $events = $identity->pullDomainEvents();

        self::assertCount(4, $events);
        self::assertInstanceOf(IdentityCreated::class, $events[0]);
        self::assertInstanceOf(IdentityActivated::class, $events[1]);
        self::assertInstanceOf(SessionStarted::class, $events[2]);
        self::assertInstanceOf(WorkspaceSelected::class, $events[3]);
        self::assertSame('identity.created', $events[0]->name());
        self::assertSame('workspace.selected', $events[3]->name());
    }
}
