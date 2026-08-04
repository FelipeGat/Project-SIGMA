<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;

final class InMemoryTenantRepository implements TenantRepository
{
    /** @var array<string, Tenant> */
    private array $tenants = [];

    public function save(Tenant $tenant): void
    {
        $this->tenants[$tenant->id()->toString()] = $tenant;
    }

    public function find(TenantId $id): ?Tenant
    {
        return $this->tenants[$id->toString()] ?? null;
    }
}
