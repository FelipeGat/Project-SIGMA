<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;

final class PdoTenantRepository implements TenantRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Tenant $tenant): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO tenants (id, name, created_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
        );
        $statement->execute([$tenant->id()->toString(), $tenant->name()]);
    }

    public function find(TenantId $id): ?Tenant
    {
        $statement = $this->pdo->prepare('SELECT id, name FROM tenants WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : new Tenant(TenantId::fromString($row['id']), $row['name']);
    }
}
