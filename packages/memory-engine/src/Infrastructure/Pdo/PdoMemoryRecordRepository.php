<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Pdo;

use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MemoryRecordId;
use Sigma\MemoryEngine\Domain\MemoryRecordStatus;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class PdoMemoryRecordRepository implements MemoryRecordRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(MemoryRecord $record): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO memory_records
                (id, level, subject_key, content, confidence, origin, status, tenant_id, workspace_id, mission_id, promoted_from, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), confidence = VALUES(confidence)',
        );
        $statement->execute([
            $record->id()->toString(),
            $record->level()->value,
            $record->subjectKey(),
            $record->content(),
            $record->confidence(),
            $record->origin(),
            $record->status()->value,
            $record->tenantId()->toString(),
            $record->workspaceId()?->toString(),
            $record->missionId()?->toString(),
            $record->promotedFrom()?->toString(),
        ]);

        $this->saveSourceMissions($record);
    }

    private function saveSourceMissions(MemoryRecord $record): void
    {
        $delete = $this->pdo->prepare('DELETE FROM memory_record_source_missions WHERE memory_record_id = ?');
        $delete->execute([$record->id()->toString()]);

        $insert = $this->pdo->prepare('INSERT IGNORE INTO memory_record_source_missions (memory_record_id, mission_id) VALUES (?, ?)');
        foreach ($record->sourceMissionIds() as $missionId) {
            $insert->execute([$record->id()->toString(), $missionId->toString()]);
        }
    }

    public function find(MemoryRecordId $id): ?MemoryRecord
    {
        $statement = $this->pdo->prepare('SELECT * FROM memory_records WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findReinforcingMissionIds(string $subjectKey, WorkspaceId $workspaceId, MemoryRecordId $excluding, TenantId $tenantId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT sm.mission_id
             FROM memory_record_source_missions sm
             INNER JOIN memory_records mr ON mr.id = sm.memory_record_id
             WHERE mr.subject_key = ? AND mr.workspace_id = ? AND mr.tenant_id = ? AND mr.id != ? AND mr.status = ?',
        );
        $statement->execute([$subjectKey, $workspaceId->toString(), $tenantId->toString(), $excluding->toString(), MemoryRecordStatus::Active->value]);

        return array_map(
            static fn (string $missionId) => MissionId::fromString($missionId),
            $statement->fetchAll(\PDO::FETCH_COLUMN),
        );
    }

    public function findReinforcingWorkspaceIds(string $subjectKey, WorkspaceId $excluding, TenantId $tenantId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT workspace_id FROM memory_records
             WHERE subject_key = ? AND tenant_id = ? AND level = ? AND status = ? AND workspace_id != ?',
        );
        $statement->execute([$subjectKey, $tenantId->toString(), MemoryLevel::Project->value, MemoryRecordStatus::Active->value, $excluding->toString()]);

        return array_map(
            static fn (string $workspaceId) => WorkspaceId::fromString($workspaceId),
            $statement->fetchAll(\PDO::FETCH_COLUMN),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): MemoryRecord
    {
        $missions = $this->pdo->prepare('SELECT mission_id FROM memory_record_source_missions WHERE memory_record_id = ?');
        $missions->execute([$row['id']]);
        $sourceMissionIds = array_map(
            static fn (string $missionId) => MissionId::fromString($missionId),
            $missions->fetchAll(\PDO::FETCH_COLUMN),
        );

        return MemoryRecord::reconstitute(
            MemoryRecordId::fromString($row['id']),
            MemoryLevel::from($row['level']),
            $row['subject_key'],
            $row['content'],
            (float) $row['confidence'],
            $row['origin'],
            MemoryRecordStatus::from($row['status']),
            TenantId::fromString($row['tenant_id']),
            $row['workspace_id'] === null ? null : WorkspaceId::fromString($row['workspace_id']),
            $row['mission_id'] === null ? null : MissionId::fromString($row['mission_id']),
            $sourceMissionIds,
            $row['promoted_from'] === null ? null : MemoryRecordId::fromString($row['promoted_from']),
        );
    }
}
