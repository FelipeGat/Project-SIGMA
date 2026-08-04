<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;

interface TenantRepository
{
    public function save(Tenant $tenant): void;

    public function find(TenantId $id): ?Tenant;
}
