<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

/**
 * Uma Subtask falhou definitivamente sem ter produzido efeito
 * colateral — a Mission vai direto a `Failed`, sem passar por
 * `Compensating`. Achado real durante a Implementation (ver
 * MISSION_EVENTS.md) — o caminho com compensação já tinha
 * `MissionCompensationFinished`, este faltava.
 */
final class MissionFailed implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly SubtaskId $subtaskId,
    ) {
    }

    public function name(): string
    {
        return 'mission.failed';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'subtaskId' => $this->subtaskId->toString(),
        ];
    }
}
