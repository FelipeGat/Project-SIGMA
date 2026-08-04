<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application;

use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;

interface MissionRepository
{
    public function save(Mission $mission): void;

    public function find(MissionId $id): ?Mission;
}
