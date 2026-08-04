<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;

final class CancelMission
{
    public function __construct(
        private readonly MissionRepository $missions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(MissionId $id, string $reason, \DateTimeImmutable $now): Mission
    {
        $mission = $this->missions->find($id);
        if ($mission === null) {
            throw new SigmaException('Mission não encontrada.', 'mission.not_found');
        }

        $mission->cancel($reason, $now);

        $this->missions->save($mission);
        foreach ($mission->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $mission;
    }
}
