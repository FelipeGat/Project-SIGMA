<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\IdentityEngine\Domain\Event\PermissionGranted;
use Sigma\IdentityEngine\Domain\Event\PermissionRevoked;
use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\TenantId;

final class RoleTest extends TestCase
{
    public function test_a_role_created_with_permissions_has_them(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial', [Permission::fromKey('mission.create')]);

        self::assertTrue($role->hasPermission('mission.create'));
    }

    public function test_grant_permission_adds_it_and_records_permission_granted(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');

        $role->grantPermission(Permission::fromKey('budget.approve'));

        self::assertTrue($role->hasPermission('budget.approve'));
        $events = $role->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PermissionGranted::class, $events[0]);
        self::assertSame('permission.granted', $events[0]->name());
    }

    public function test_granting_an_already_granted_permission_does_not_record_a_duplicate_event(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial', [Permission::fromKey('mission.create')]);

        $role->grantPermission(Permission::fromKey('mission.create'));

        self::assertCount(0, $role->pullDomainEvents());
    }

    public function test_revoke_permission_removes_it_and_records_permission_revoked(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial', [Permission::fromKey('mission.create')]);
        $role->pullDomainEvents();

        $role->revokePermission(Permission::fromKey('mission.create'));

        self::assertFalse($role->hasPermission('mission.create'));
        $events = $role->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PermissionRevoked::class, $events[0]);
    }

    public function test_pull_domain_events_clears_the_pending_events(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Comercial');
        $role->grantPermission(Permission::fromKey('mission.create'));

        $role->pullDomainEvents();

        self::assertCount(0, $role->pullDomainEvents());
    }

    public function test_can_autonomously_reflects_the_named_capability(): void
    {
        $role = new Role(
            RoleId::generate(),
            TenantId::generate(),
            'Financeiro',
            autonomyCapabilities: ['CanApproveBudget' => true, 'CanDeleteMission' => false],
        );

        self::assertTrue($role->canAutonomously('CanApproveBudget'));
        self::assertFalse($role->canAutonomously('CanDeleteMission'));
    }

    public function test_can_autonomously_defaults_to_false_for_an_unlisted_capability(): void
    {
        $role = new Role(RoleId::generate(), TenantId::generate(), 'Financeiro');

        self::assertFalse($role->canAutonomously('CanDeploy'));
    }
}
