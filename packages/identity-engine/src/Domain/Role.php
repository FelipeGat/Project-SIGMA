<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\IdentityEngine\Domain\Event\PermissionGranted;
use Sigma\IdentityEngine\Domain\Event\PermissionRevoked;

/**
 * Um conjunto nomeado de Permissions, aplicável a um User ou Team num
 * escopo (via RoleAssignment) — IDENTITY_MODEL.md#role. Autonomia é
 * baseada em capability nomeada, não em nível numérico — ver ADR-0068.
 */
final class Role
{
    use RecordsDomainEvents;

    /** @var array<string, Permission> chave = Permission::key() */
    private array $permissions;

    /** @var array<string, bool> chave = nome da capability (ex: "CanApproveBudget") */
    private array $autonomyCapabilities;

    /**
     * @param list<Permission> $permissions
     * @param array<string, bool> $autonomyCapabilities
     */
    public function __construct(
        private readonly RoleId $id,
        private readonly TenantId $tenantId,
        private readonly string $name,
        array $permissions = [],
        array $autonomyCapabilities = [],
    ) {
        $this->permissions = [];
        foreach ($permissions as $permission) {
            $this->permissions[$permission->key()] = $permission;
        }

        $this->autonomyCapabilities = $autonomyCapabilities;
    }

    public function id(): RoleId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return array_values($this->permissions);
    }

    public function hasPermission(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    /** @return array<string, bool> */
    public function autonomyCapabilities(): array
    {
        return $this->autonomyCapabilities;
    }

    public function canAutonomously(string $capabilityKey): bool
    {
        return $this->autonomyCapabilities[$capabilityKey] ?? false;
    }

    public function grantPermission(Permission $permission): void
    {
        if (isset($this->permissions[$permission->key()])) {
            return;
        }

        $this->permissions[$permission->key()] = $permission;
        $this->record(new PermissionGranted($this->id, $permission));
    }

    public function revokePermission(Permission $permission): void
    {
        if (!isset($this->permissions[$permission->key()])) {
            return;
        }

        unset($this->permissions[$permission->key()]);
        $this->record(new PermissionRevoked($this->id, $permission));
    }
}
