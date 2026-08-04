<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Fronteira de isolamento total de dados — o nível mais alto da
 * hierarquia (IDENTITY_MODEL.md#tenant).
 */
final class Tenant
{
    public function __construct(
        private readonly TenantId $id,
        private readonly string $name,
    ) {
    }

    public function id(): TenantId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
