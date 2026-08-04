<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Domain\Permission;

final class PermissionTest extends TestCase
{
    public function test_accepts_a_valid_resource_dot_action_key(): void
    {
        $permission = Permission::fromKey('mission.create');

        self::assertSame('mission.create', $permission->key());
    }

    #[DataProvider('invalidKeys')]
    public function test_rejects_a_key_that_is_not_resource_dot_action(string $invalid): void
    {
        $this->expectException(SigmaException::class);

        Permission::fromKey($invalid);
    }

    /** @return list<list<string>> */
    public static function invalidKeys(): array
    {
        return [
            [''],
            ['mission'],
            ['Mission.Create'],
            ['mission.'],
            ['.create'],
            ['mission create'],
        ];
    }

    public function test_two_permissions_with_the_same_key_are_equal(): void
    {
        self::assertTrue(Permission::fromKey('mission.create')->equals(Permission::fromKey('mission.create')));
    }

    public function test_two_permissions_with_different_keys_are_not_equal(): void
    {
        self::assertFalse(Permission::fromKey('mission.create')->equals(Permission::fromKey('budget.approve')));
    }
}
