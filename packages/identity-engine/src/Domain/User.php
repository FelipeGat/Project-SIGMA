<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Uma pessoa com acesso ao SIGMA, associada a exatamente um Tenant
 * (IDENTITY_MODEL.md#user).
 */
final class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly TenantId $tenantId,
        private readonly string $name,
        private readonly string $email,
    ) {
    }

    public function id(): UserId
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

    public function email(): string
    {
        return $this->email;
    }
}
