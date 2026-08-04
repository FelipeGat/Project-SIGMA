<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Pdo;

use Sigma\MemoryEngine\Application\ContextMemoryRepository;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\ContextMemoryId;
use Sigma\MemoryEngine\Domain\ContextMemoryStatus;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class PdoContextMemoryRepository implements ContextMemoryRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(ContextMemory $contextMemory): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO context_memories (id, tenant_id, workspace_id, mission_id, origin, raw_content, started_at, ended_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE raw_content = VALUES(raw_content), ended_at = VALUES(ended_at), status = VALUES(status)',
        );
        $statement->execute([
            $contextMemory->id()->toString(),
            $contextMemory->tenantId()->toString(),
            $contextMemory->workspaceId()->toString(),
            $contextMemory->missionId()?->toString(),
            $contextMemory->origin(),
            $contextMemory->rawContent(),
            $contextMemory->startedAt()->format('Y-m-d H:i:s'),
            $contextMemory->endedAt()?->format('Y-m-d H:i:s'),
            $contextMemory->status()->value,
        ]);
    }

    public function find(ContextMemoryId $id): ?ContextMemory
    {
        $statement = $this->pdo->prepare('SELECT * FROM context_memories WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ContextMemory::reconstitute(
            ContextMemoryId::fromString($row['id']),
            TenantId::fromString($row['tenant_id']),
            WorkspaceId::fromString($row['workspace_id']),
            $row['mission_id'] === null ? null : MissionId::fromString($row['mission_id']),
            $row['origin'],
            $row['raw_content'],
            new \DateTimeImmutable($row['started_at']),
            $row['ended_at'] === null ? null : new \DateTimeImmutable($row['ended_at']),
            ContextMemoryStatus::from($row['status']),
        );
    }
}
