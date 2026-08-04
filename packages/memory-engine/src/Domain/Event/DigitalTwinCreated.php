<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\DigitalTwinId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

final class DigitalTwinCreated implements DomainEvent
{
    public function __construct(
        public readonly DigitalTwinId $digitalTwinId,
        public readonly TwinSubjectType $subjectType,
        public readonly string $externalRef,
    ) {
    }

    public function name(): string
    {
        return 'digital_twin.created';
    }

    public function payload(): array
    {
        return [
            'digitalTwinId' => $this->digitalTwinId->toString(),
            'subjectType' => $this->subjectType->value,
            'externalRef' => $this->externalRef,
        ];
    }
}
