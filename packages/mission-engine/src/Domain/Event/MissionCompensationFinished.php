<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;

final class MissionCompensationFinished implements DomainEvent
{
    public function __construct(public readonly MissionId $missionId)
    {
    }

    public function name(): string
    {
        return 'mission.compensation_finished';
    }

    public function payload(): array
    {
        return ['missionId' => $this->missionId->toString()];
    }
}
