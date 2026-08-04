<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Pdo;

use Sigma\MemoryEngine\Application\KnowledgeRecordRepository;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\KnowledgeRecordId;
use Sigma\MemoryEngine\Domain\TenantId;

final class PdoKnowledgeRecordRepository implements KnowledgeRecordRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(KnowledgeRecord $record): void
    {
        // Imutável (ADR-0086) — sempre um INSERT novo, nunca um UPDATE.
        $statement = $this->pdo->prepare(
            'INSERT INTO knowledge_records (id, area, source_path, title, content, version, previous_version_id, tenant_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        );
        $statement->execute([
            $record->id()->toString(),
            $record->area(),
            $record->sourcePath(),
            $record->title(),
            $record->content(),
            $record->version(),
            $record->previousVersionId()?->toString(),
            $record->tenantId()->toString(),
        ]);
    }

    public function findLatestBySourcePath(string $sourcePath, TenantId $tenantId): ?KnowledgeRecord
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM knowledge_records WHERE source_path = ? AND tenant_id = ? ORDER BY version DESC LIMIT 1',
        );
        $statement->execute([$sourcePath, $tenantId->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return KnowledgeRecord::reconstitute(
            KnowledgeRecordId::fromString($row['id']),
            $row['area'],
            $row['source_path'],
            $row['title'],
            $row['content'],
            (int) $row['version'],
            $row['previous_version_id'] === null ? null : KnowledgeRecordId::fromString($row['previous_version_id']),
            TenantId::fromString($row['tenant_id']),
        );
    }
}
