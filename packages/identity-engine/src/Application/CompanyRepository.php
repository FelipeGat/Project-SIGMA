<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;

interface CompanyRepository
{
    public function save(Company $company): void;

    public function find(CompanyId $id): ?Company;
}
