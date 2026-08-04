<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\ContextMemoryRepository;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class StartContextMemory
{
    public function __construct(
        private readonly ContextMemoryRepository $contextMemories,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(TenantId $tenantId, WorkspaceId $workspaceId, ?MissionId $missionId, string $origin, \DateTimeImmutable $now): ContextMemory
    {
        $contextMemory = ContextMemory::start($tenantId, $workspaceId, $missionId, $origin, $now);
        $this->contextMemories->save($contextMemory);

        foreach ($contextMemory->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $contextMemory;
    }
}
