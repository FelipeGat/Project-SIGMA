<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\IdentityEngine\Domain\Event\RoleAssigned;
use Sigma\IdentityEngine\Domain\Event\RoleRevoked;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class RoleAssignmentTest extends TestCase
{
    public function test_assign_records_role_assigned(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $userId = UserId::generate();
        $scope = Scope::workspace(WorkspaceId::generate());

        $assignment = RoleAssignment::assign($role, SubjectType::User, $userId, $scope);

        $events = $assignment->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(RoleAssigned::class, $events[0]);
        self::assertFalse($assignment->isRevoked());
    }

    public function test_revoke_records_role_revoked_and_marks_as_revoked(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $assignment = RoleAssignment::assign($role, SubjectType::User, UserId::generate(), Scope::workspace(WorkspaceId::generate()));
        $assignment->pullDomainEvents();

        $assignment->revoke();

        self::assertTrue($assignment->isRevoked());
        $events = $assignment->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(RoleRevoked::class, $events[0]);
    }

    public function test_revoking_twice_records_the_event_only_once(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $assignment = RoleAssignment::assign($role, SubjectType::User, UserId::generate(), Scope::workspace(WorkspaceId::generate()));
        $assignment->pullDomainEvents();

        $assignment->revoke();
        $assignment->revoke();

        self::assertCount(1, $assignment->pullDomainEvents());
    }

    public function test_reconstitute_restores_the_revoked_flag_without_recording_any_event(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $scope = Scope::workspace(WorkspaceId::generate());
        $userId = UserId::generate();

        $assignment = RoleAssignment::reconstitute($role, SubjectType::User, $userId, $scope, true);

        self::assertTrue($assignment->isRevoked());
        self::assertCount(0, $assignment->pullDomainEvents());
    }

    public function test_applies_to_scope_matches_exact_scope_only(): void
    {
        $workspaceId = WorkspaceId::generate();
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $assignment = RoleAssignment::assign($role, SubjectType::User, UserId::generate(), Scope::workspace($workspaceId));

        self::assertTrue($assignment->appliesToScope(Scope::workspace($workspaceId)));
        self::assertFalse($assignment->appliesToScope(Scope::workspace(WorkspaceId::generate())));
    }
}
