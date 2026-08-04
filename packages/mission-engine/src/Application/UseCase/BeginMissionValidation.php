<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;

final class BeginMissionValidation
{
    public function __construct(
        private readonly MissionRepository $missions,
    ) {
    }

    public function execute(MissionId $id, \DateTimeImmutable $now): Mission
    {
        $mission = $this->missions->find($id);
        if ($mission === null) {
            throw new SigmaException('Mission não encontrada.', 'mission.not_found');
        }

        $mission->beginValidation($now);
        $this->missions->save($mission);

        return $mission;
    }
}
