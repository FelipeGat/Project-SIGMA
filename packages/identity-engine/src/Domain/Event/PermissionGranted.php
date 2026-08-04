<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\RoleId;

final class PermissionGranted implements DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly Permission $permission,
    ) {
    }

    public function name(): string
    {
        return 'permission.granted';
    }

    public function payload(): array
    {
        return [
            'roleId' => $this->roleId->toString(),
            'permissionKey' => $this->permission->key(),
        ];
    }
}
