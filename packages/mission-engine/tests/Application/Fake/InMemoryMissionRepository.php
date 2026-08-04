<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Application\Fake;

use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;

final class InMemoryMissionRepository implements MissionRepository
{
    /** @var array<string, Mission> */
    private array $missions = [];

    public function save(Mission $mission): void
    {
        $this->missions[$mission->id()->toString()] = $mission;
    }

    public function find(MissionId $id): ?Mission
    {
        return $this->missions[$id->toString()] ?? null;
    }
}
