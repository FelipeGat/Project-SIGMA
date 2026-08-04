<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;

final class MissionFinished implements DomainEvent
{
    public function __construct(public readonly MissionId $missionId)
    {
    }

    public function name(): string
    {
        return 'mission.finished';
    }

    public function payload(): array
    {
        return ['missionId' => $this->missionId->toString()];
    }
}
