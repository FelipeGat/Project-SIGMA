<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\SubtaskId;

final class FailMissionValidation
{
    public function __construct(
        private readonly MissionRepository $missions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(MissionId $id, SubtaskId $faultingSubtaskId, bool $hasProducedEffect, \DateTimeImmutable $now): Mission
    {
        $mission = $this->missions->find($id);
        if ($mission === null) {
            throw new SigmaException('Mission não encontrada.', 'mission.not_found');
        }

        $mission->failValidation($faultingSubtaskId, $hasProducedEffect, $now);

        $this->missions->save($mission);
        foreach ($mission->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $mission;
    }
}
