<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Pdo;

use Sigma\MemoryEngine\Application\PinnedMemorySubject;
use Sigma\MemoryEngine\Application\PinnedMemorySubjectRepository;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class PdoPinnedMemorySubjectRepository implements PinnedMemorySubjectRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(PinnedMemorySubject $pin): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO pinned_memory_subjects (subject_key, workspace_id, tenant_id, actor, pinned_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE actor = VALUES(actor), pinned_at = VALUES(pinned_at)',
        );
        $statement->execute([
            $pin->subjectKey,
            $pin->workspaceId->toString(),
            $pin->tenantId->toString(),
            $pin->actor,
            $pin->pinnedAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function find(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): ?PinnedMemorySubject
    {
        $statement = $this->pdo->prepare('SELECT * FROM pinned_memory_subjects WHERE subject_key = ? AND workspace_id = ? AND tenant_id = ?');
        $statement->execute([$subjectKey, $workspaceId->toString(), $tenantId->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new PinnedMemorySubject(
            $row['subject_key'],
            WorkspaceId::fromString($row['workspace_id']),
            TenantId::fromString($row['tenant_id']),
            $row['actor'],
            new \DateTimeImmutable($row['pinned_at']),
        );
    }

    public function delete(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM pinned_memory_subjects WHERE subject_key = ? AND workspace_id = ? AND tenant_id = ?');
        $statement->execute([$subjectKey, $workspaceId->toString(), $tenantId->toString()]);
    }
}
