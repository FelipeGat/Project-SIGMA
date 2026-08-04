<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

final class MissionCompensationStarted implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly SubtaskId $subtaskId,
    ) {
    }

    public function name(): string
    {
        return 'mission.compensation_started';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'subtaskId' => $this->subtaskId->toString(),
        ];
    }
}
