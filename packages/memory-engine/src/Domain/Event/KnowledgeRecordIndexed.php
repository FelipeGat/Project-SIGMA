<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\KnowledgeRecordId;

final class KnowledgeRecordIndexed implements DomainEvent
{
    public function __construct(
        public readonly KnowledgeRecordId $knowledgeRecordId,
        public readonly string $area,
        public readonly string $sourcePath,
        public readonly int $version,
    ) {
    }

    public function name(): string
    {
        return 'knowledge.indexed';
    }

    public function payload(): array
    {
        return [
            'knowledgeRecordId' => $this->knowledgeRecordId->toString(),
            'area' => $this->area,
            'sourcePath' => $this->sourcePath,
            'version' => $this->version,
        ];
    }
}
