<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Team;
use Sigma\IdentityEngine\Domain\TeamId;

interface TeamRepository
{
    public function save(Team $team): void;

    public function find(TeamId $id): ?Team;

    /** @return list<Team> */
    public function findByCompany(CompanyId $companyId): array;
}
