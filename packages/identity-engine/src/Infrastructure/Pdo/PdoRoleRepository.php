<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\RoleRepository;
use Sigma\IdentityEngine\Domain\Permission;
use Sigma\IdentityEngine\Domain\Role;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\TenantId;

final class PdoRoleRepository implements RoleRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Role $role): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO roles (id, tenant_id, name, autonomy_capabilities, created_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), autonomy_capabilities = VALUES(autonomy_capabilities)',
        );
        $statement->execute([
            $role->id()->toString(),
            $role->tenantId()->toString(),
            $role->name(),
            json_encode($role->autonomyCapabilities(), \JSON_THROW_ON_ERROR),
        ]);

        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$role->id()->toString()]);

        $insertPermission = $this->pdo->prepare('INSERT INTO role_permissions (role_id, permission_key) VALUES (?, ?)');
        foreach ($role->permissions() as $permission) {
            $insertPermission->execute([$role->id()->toString(), $permission->key()]);
        }
    }

    public function find(RoleId $id): ?Role
    {
        $statement = $this->pdo->prepare('SELECT id, tenant_id, name, autonomy_capabilities FROM roles WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $permissionsStatement = $this->pdo->prepare('SELECT permission_key FROM role_permissions WHERE role_id = ?');
        $permissionsStatement->execute([$row['id']]);
        $permissions = array_map(
            static fn (string $key): Permission => Permission::fromKey($key),
            $permissionsStatement->fetchAll(\PDO::FETCH_COLUMN),
        );

        return new Role(
            RoleId::fromString($row['id']),
            TenantId::fromString($row['tenant_id']),
            $row['name'],
            $permissions,
            json_decode($row['autonomy_capabilities'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }
}
