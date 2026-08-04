<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\WorkspaceRepository;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class PdoWorkspaceRepository implements WorkspaceRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Workspace $workspace): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO workspaces (id, company_id, name, created_at) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
        );
        $statement->execute([$workspace->id()->toString(), $workspace->companyId()->toString(), $workspace->name()]);
    }

    public function find(WorkspaceId $id): ?Workspace
    {
        $statement = $this->pdo->prepare('SELECT id, company_id, name FROM workspaces WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : new Workspace(WorkspaceId::fromString($row['id']), CompanyId::fromString($row['company_id']), $row['name']);
    }
}
