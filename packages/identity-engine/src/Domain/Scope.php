<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * O nível em que um RoleAssignment vale — um Tenant, uma Company ou um
 * Workspace específico (IDENTITY_MODEL.md#relações). Imutável, igualdade
 * por valor.
 */
final class Scope
{
    private function __construct(
        public readonly ScopeType $type,
        public readonly Identifier $id,
    ) {
    }

    public static function tenant(TenantId $id): self
    {
        return new self(ScopeType::Tenant, $id);
    }

    public static function company(CompanyId $id): self
    {
        return new self(ScopeType::Company, $id);
    }

    public static function workspace(WorkspaceId $id): self
    {
        return new self(ScopeType::Workspace, $id);
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->id->equals($other->id);
    }
}
