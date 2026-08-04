<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\IntentId;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

final class MissionCreated implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly CorrelationId $correlationId,
        public readonly TenantId $tenantId,
        public readonly ?WorkspaceId $workspaceId,
        public readonly ?IntentId $intentId,
    ) {
    }

    public function name(): string
    {
        return 'mission.created';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'correlationId' => $this->correlationId->toString(),
            'tenantId' => $this->tenantId->toString(),
            'workspaceId' => $this->workspaceId?->toString(),
            'intentId' => $this->intentId?->toString(),
        ];
    }
}
