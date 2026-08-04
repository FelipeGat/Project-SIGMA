<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\CompanyRepository;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;

final class InMemoryCompanyRepository implements CompanyRepository
{
    /** @var array<string, Company> */
    private array $companies = [];

    public function save(Company $company): void
    {
        $this->companies[$company->id()->toString()] = $company;
    }

    public function find(CompanyId $id): ?Company
    {
        return $this->companies[$id->toString()] ?? null;
    }
}
