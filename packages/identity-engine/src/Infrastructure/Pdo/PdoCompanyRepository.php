<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\CompanyRepository;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\TenantId;

final class PdoCompanyRepository implements CompanyRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Company $company): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (id, tenant_id, name, created_at) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
        );
        $statement->execute([$company->id()->toString(), $company->tenantId()->toString(), $company->name()]);
    }

    public function find(CompanyId $id): ?Company
    {
        $statement = $this->pdo->prepare('SELECT id, tenant_id, name FROM companies WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : new Company(CompanyId::fromString($row['id']), TenantId::fromString($row['tenant_id']), $row['name']);
    }
}
