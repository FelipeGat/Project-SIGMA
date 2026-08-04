<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Uma empresa dentro de um Tenant (IDENTITY_MODEL.md#company).
 */
final class Company
{
    public function __construct(
        private readonly CompanyId $id,
        private readonly TenantId $tenantId,
        private readonly string $name,
    ) {
    }

    public function id(): CompanyId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
