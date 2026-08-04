<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

final class SubtasksCreated implements DomainEvent
{
    /** @param list<SubtaskId> $subtaskIds */
    public function __construct(
        public readonly MissionId $missionId,
        public readonly array $subtaskIds,
    ) {
    }

    public function name(): string
    {
        return 'subtasks.created';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'subtaskIds' => array_map(static fn (SubtaskId $id) => $id->toString(), $this->subtaskIds),
        ];
    }
}
