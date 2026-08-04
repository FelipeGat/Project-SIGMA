<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\MemoryRecordId;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class MemoryRecordObserved implements DomainEvent
{
    public function __construct(
        public readonly MemoryRecordId $memoryRecordId,
        public readonly string $subjectKey,
        public readonly WorkspaceId $workspaceId,
        public readonly MissionId $missionId,
        public readonly float $confidence,
        public readonly string $origin,
    ) {
    }

    public function name(): string
    {
        return 'memory.record_observed';
    }

    public function payload(): array
    {
        return [
            'memoryRecordId' => $this->memoryRecordId->toString(),
            'subjectKey' => $this->subjectKey,
            'workspaceId' => $this->workspaceId->toString(),
            'missionId' => $this->missionId->toString(),
            'confidence' => $this->confidence,
            'origin' => $this->origin,
        ];
    }
}
