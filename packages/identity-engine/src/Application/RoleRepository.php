<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleId;

interface RoleRepository
{
    public function save(Role $role): void;

    public function find(RoleId $id): ?Role;
}
