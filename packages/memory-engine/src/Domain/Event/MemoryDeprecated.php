<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\MemoryRecordId;

final class MemoryDeprecated implements DomainEvent
{
    public function __construct(
        public readonly MemoryRecordId $memoryRecordId,
        public readonly string $subjectKey,
        public readonly MemoryRecordId $contradictedBy,
    ) {
    }

    public function name(): string
    {
        return 'memory.deprecated';
    }

    public function payload(): array
    {
        return [
            'memoryRecordId' => $this->memoryRecordId->toString(),
            'subjectKey' => $this->subjectKey,
            'contradictedBy' => $this->contradictedBy->toString(),
        ];
    }
}
