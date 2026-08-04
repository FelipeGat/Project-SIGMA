<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\ContextMemoryId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class ContextMemoryClosed implements DomainEvent
{
    public function __construct(
        public readonly ContextMemoryId $contextMemoryId,
        public readonly WorkspaceId $workspaceId,
        public readonly \DateTimeImmutable $endedAt,
    ) {
    }

    public function name(): string
    {
        return 'context_memory.closed';
    }

    public function payload(): array
    {
        return [
            'contextMemoryId' => $this->contextMemoryId->toString(),
            'workspaceId' => $this->workspaceId->toString(),
            'endedAt' => $this->endedAt->format(DATE_ATOM),
        ];
    }
}
