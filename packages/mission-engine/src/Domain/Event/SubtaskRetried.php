<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

final class SubtaskRetried implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly SubtaskId $subtaskId,
        public readonly int $attemptNumber,
        public readonly string $reason,
    ) {
    }

    public function name(): string
    {
        return 'subtask.retried';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'subtaskId' => $this->subtaskId->toString(),
            'attemptNumber' => $this->attemptNumber,
            'reason' => $this->reason,
        ];
    }
}
