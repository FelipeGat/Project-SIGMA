<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\RoleRepository;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleId;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var array<string, Role> */
    private array $roles = [];

    public function save(Role $role): void
    {
        $this->roles[$role->id()->toString()] = $role;
    }

    public function find(RoleId $id): ?Role
    {
        return $this->roles[$id->toString()] ?? null;
    }
}
