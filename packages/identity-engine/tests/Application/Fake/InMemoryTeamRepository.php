<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\TeamRepository;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Team;
use Sigma\IdentityEngine\Domain\TeamId;

final class InMemoryTeamRepository implements TeamRepository
{
    /** @var array<string, Team> */
    private array $teams = [];

    public function save(Team $team): void
    {
        $this->teams[$team->id()->toString()] = $team;
    }

    public function find(TeamId $id): ?Team
    {
        return $this->teams[$id->toString()] ?? null;
    }

    public function findByCompany(CompanyId $companyId): array
    {
        return array_values(array_filter(
            $this->teams,
            static fn (Team $team): bool => $team->companyId()->equals($companyId),
        ));
    }
}
