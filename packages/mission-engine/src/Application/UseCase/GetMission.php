<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;

/** Consulta, sem side-effect — nunca publica evento algum. */
final class GetMission
{
    public function __construct(
        private readonly MissionRepository $missions,
    ) {
    }

    public function execute(MissionId $id): ?Mission
    {
        return $this->missions->find($id);
    }
}
