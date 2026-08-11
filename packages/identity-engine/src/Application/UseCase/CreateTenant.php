<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Domain\Tenant;
use Sigma\IdentityEngine\Domain\TenantId;

/**
 * Cria o Tenant — o topo da hierarquia de MULTITENANCY.md, e a única
 * entidade que `RegisterIdentity` exige já existir sem que nada no
 * sistema soubesse criá-la (achado da Release 5.5: até aqui,
 * Tenant/Company/Workspace só nasciam em fixtures de teste).
 *
 * Não publica evento de domínio: `Tenant` não é um aggregate com
 * eventos próprios no modelo atual (ver IDENTITY_MODEL.md#tenant), e
 * inventar um `tenant.created` aqui exigiria catalogá-lo em
 * EVENT_CATALOG.md — decisão de modelagem que esta Release não toma.
 */
final class CreateTenant
{
    public function __construct(private readonly TenantRepository $tenants)
    {
    }

    public function execute(string $name): Tenant
    {
        $tenant = new Tenant(TenantId::generate(), $name);
        $this->tenants->save($tenant);

        return $tenant;
    }
}
