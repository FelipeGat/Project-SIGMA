<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;

final class MissionCancelled implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly string $reason,
    ) {
    }

    public function name(): string
    {
        return 'mission.cancelled';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'reason' => $this->reason,
        ];
    }
}
