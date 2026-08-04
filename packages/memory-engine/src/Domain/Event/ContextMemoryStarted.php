<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\ContextMemoryId;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class ContextMemoryStarted implements DomainEvent
{
    public function __construct(
        public readonly ContextMemoryId $contextMemoryId,
        public readonly WorkspaceId $workspaceId,
        public readonly ?MissionId $missionId,
        public readonly string $origin,
    ) {
    }

    public function name(): string
    {
        return 'context_memory.started';
    }

    public function payload(): array
    {
        return [
            'contextMemoryId' => $this->contextMemoryId->toString(),
            'workspaceId' => $this->workspaceId->toString(),
            'missionId' => $this->missionId?->toString(),
            'origin' => $this->origin,
        ];
    }
}
