<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecordId;

final class MemoryPromoted implements DomainEvent
{
    public function __construct(
        public readonly MemoryRecordId $memoryRecordId,
        public readonly string $subjectKey,
        public readonly MemoryLevel $fromLevel,
        public readonly MemoryLevel $toLevel,
        public readonly float $confidence,
        public readonly MemoryRecordId $promotedFrom,
    ) {
    }

    public function name(): string
    {
        return 'memory.promoted';
    }

    public function payload(): array
    {
        return [
            'memoryRecordId' => $this->memoryRecordId->toString(),
            'subjectKey' => $this->subjectKey,
            'fromLevel' => $this->fromLevel->value,
            'toLevel' => $this->toLevel->value,
            'confidence' => $this->confidence,
            'promotedFrom' => $this->promotedFrom->toString(),
        ];
    }
}
