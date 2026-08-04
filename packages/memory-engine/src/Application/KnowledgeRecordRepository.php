<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

interface KnowledgeRecordRepository
{
    public function save(KnowledgeRecord $record): void;

    /** A versão corrente (maior `version`) de uma linhagem — ou null se `sourcePath` nunca foi indexado. */
    public function findLatestBySourcePath(string $sourcePath, TenantId $tenantId): ?KnowledgeRecord;
}
