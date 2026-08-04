<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Context;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class ContextTest extends TestCase
{
    public function test_has_permission_reflects_the_granted_set(): void
    {
        $context = new Context(
            IdentityId::generate(),
            UserId::generate(),
            TenantId::generate(),
            CompanyId::generate(),
            WorkspaceId::generate(),
            ['mission.create' => true],
            [],
        );

        self::assertTrue($context->hasPermission('mission.create'));
        self::assertFalse($context->hasPermission('budget.approve'));
    }

    public function test_can_autonomously_defaults_to_false_for_an_unknown_capability(): void
    {
        $context = new Context(
            IdentityId::generate(),
            UserId::generate(),
            TenantId::generate(),
            CompanyId::generate(),
            WorkspaceId::generate(),
            [],
            ['CanApproveBudget' => true],
        );

        self::assertTrue($context->canAutonomously('CanApproveBudget'));
        self::assertFalse($context->canAutonomously('CanDeploy'));
    }

    public function test_granted_permissions_lists_only_the_keys_granted_as_true(): void
    {
        $context = new Context(
            IdentityId::generate(),
            UserId::generate(),
            TenantId::generate(),
            CompanyId::generate(),
            WorkspaceId::generate(),
            ['mission.create' => true, 'budget.approve' => false],
            [],
        );

        self::assertSame(['mission.create'], $context->grantedPermissions());
    }
}
