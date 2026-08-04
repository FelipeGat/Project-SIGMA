<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\DigitalTwinId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

final class DigitalTwinUpdated implements DomainEvent
{
    public function __construct(
        public readonly DigitalTwinId $digitalTwinId,
        public readonly TwinSubjectType $subjectType,
        public readonly \DateTimeImmutable $lastSyncedAt,
    ) {
    }

    public function name(): string
    {
        return 'digital_twin.updated';
    }

    public function payload(): array
    {
        return [
            'digitalTwinId' => $this->digitalTwinId->toString(),
            'subjectType' => $this->subjectType->value,
            'lastSyncedAt' => $this->lastSyncedAt->format(DATE_ATOM),
        ];
    }
}
