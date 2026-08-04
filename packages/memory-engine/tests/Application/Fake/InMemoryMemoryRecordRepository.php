<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application\Fake;

use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MemoryRecordId;
use Sigma\MemoryEngine\Domain\MemoryRecordStatus;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class InMemoryMemoryRecordRepository implements MemoryRecordRepository
{
    /** @var array<string, MemoryRecord> */
    private array $records = [];

    public function save(MemoryRecord $record): void
    {
        $this->records[$record->id()->toString()] = $record;
    }

    public function find(MemoryRecordId $id): ?MemoryRecord
    {
        return $this->records[$id->toString()] ?? null;
    }

    public function findReinforcingMissionIds(string $subjectKey, WorkspaceId $workspaceId, MemoryRecordId $excluding, TenantId $tenantId): array
    {
        $missionIds = [];
        foreach ($this->records as $record) {
            if ($record->id()->equals($excluding) || $record->status() !== MemoryRecordStatus::Active) {
                continue;
            }
            if ($record->subjectKey() !== $subjectKey || !$record->tenantId()->equals($tenantId)) {
                continue;
            }
            if ($record->workspaceId() === null || !$record->workspaceId()->equals($workspaceId)) {
                continue;
            }
            foreach ($record->sourceMissionIds() as $missionId) {
                $missionIds[$missionId->toString()] = $missionId;
            }
        }

        return array_values($missionIds);
    }

    public function findReinforcingWorkspaceIds(string $subjectKey, WorkspaceId $excluding, TenantId $tenantId): array
    {
        $workspaceIds = [];
        foreach ($this->records as $record) {
            if ($record->level() !== MemoryLevel::Project || $record->status() !== MemoryRecordStatus::Active) {
                continue;
            }
            if ($record->subjectKey() !== $subjectKey || !$record->tenantId()->equals($tenantId)) {
                continue;
            }
            if ($record->workspaceId() === null || $record->workspaceId()->equals($excluding)) {
                continue;
            }
            $workspaceIds[$record->workspaceId()->toString()] = $record->workspaceId();
        }

        return array_values($workspaceIds);
    }
}
