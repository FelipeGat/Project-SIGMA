<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Identity + Session + Workspace ativo + Permissions/Autonomy
 * efetivas — o objeto que o Kernel disponibiliza a todo Engine como
 * contexto de execução. Totalmente imutável (ADR-0066): trocar de
 * Workspace nunca produz um Context diferente a partir deste — produz
 * um Context novo, a partir de uma Session nova.
 */
final class Context
{
    /**
     * @param array<string, bool> $permissions chave = Permission::key(), valor sempre true (presença = concedida)
     * @param array<string, bool> $autonomyCapabilities chave = nome da capability (ex: "CanApproveBudget")
     */
    public function __construct(
        public readonly IdentityId $identityId,
        public readonly UserId $userId,
        public readonly TenantId $tenantId,
        public readonly CompanyId $companyId,
        public readonly WorkspaceId $workspaceId,
        private readonly array $permissions,
        private readonly array $autonomyCapabilities,
    ) {
    }

    public function hasPermission(string $key): bool
    {
        return $this->permissions[$key] ?? false;
    }

    public function canAutonomously(string $capabilityKey): bool
    {
        return $this->autonomyCapabilities[$capabilityKey] ?? false;
    }

    /** @return list<string> */
    public function grantedPermissions(): array
    {
        return array_keys(array_filter($this->permissions));
    }
}
