<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\RoleRepository;
use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\Kernel\Contract\IEventBus;

final class RevokePermission
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(RoleId $roleId, string $permissionKey): void
    {
        $role = $this->roles->find($roleId);
        if ($role === null) {
            throw new SigmaException('Role não encontrado.', 'identity.role_not_found');
        }

        $role->revokePermission(Permission::fromKey($permissionKey));
        $this->roles->save($role);

        foreach ($role->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
