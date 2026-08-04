<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\CompensationResult;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

final class SubtaskCompensated implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly SubtaskId $subtaskId,
        public readonly CompensationResult $result,
    ) {
    }

    public function name(): string
    {
        return 'subtask.compensated';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'subtaskId' => $this->subtaskId->toString(),
            'result' => $this->result->value,
        ];
    }
}
