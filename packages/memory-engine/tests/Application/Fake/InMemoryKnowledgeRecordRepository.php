<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application\Fake;

use Sigma\MemoryEngine\Application\KnowledgeRecordRepository;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

final class InMemoryKnowledgeRecordRepository implements KnowledgeRecordRepository
{
    /** @var list<KnowledgeRecord> */
    private array $records = [];

    public function save(KnowledgeRecord $record): void
    {
        $this->records[] = $record;
    }

    public function findLatestBySourcePath(string $sourcePath, TenantId $tenantId): ?KnowledgeRecord
    {
        $latest = null;
        foreach ($this->records as $record) {
            if ($record->sourcePath() !== $sourcePath || !$record->tenantId()->equals($tenantId)) {
                continue;
            }
            if ($latest === null || $record->version() > $latest->version()) {
                $latest = $record;
            }
        }

        return $latest;
    }
}
