<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\MemoryRecordId;

final class MemoryRetracted implements DomainEvent
{
    public function __construct(
        public readonly MemoryRecordId $memoryRecordId,
        public readonly string $subjectKey,
        public readonly string $actor,
    ) {
    }

    public function name(): string
    {
        return 'memory.retracted';
    }

    public function payload(): array
    {
        return [
            'memoryRecordId' => $this->memoryRecordId->toString(),
            'subjectKey' => $this->subjectKey,
            'actor' => $this->actor,
        ];
    }
}
