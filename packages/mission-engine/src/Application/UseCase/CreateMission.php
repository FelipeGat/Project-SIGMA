<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\IntentId;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

final class CreateMission
{
    public function __construct(
        private readonly MissionRepository $missions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(
        TenantId $tenantId,
        ?WorkspaceId $workspaceId,
        CorrelationId $correlationId,
        ?IntentId $intentId,
        string $objective,
        Plan $plan,
        Actor $actor,
        int $autonomyCeiling,
        \DateTimeImmutable $now,
    ): Mission {
        $mission = Mission::create($tenantId, $workspaceId, $correlationId, $intentId, $objective, $plan, $actor, $autonomyCeiling, $now);

        $this->missions->save($mission);
        foreach ($mission->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $mission;
    }
}
