<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\CompanyRepository;
use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Domain\Company;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\TenantId;

/**
 * Cria uma Company dentro de um Tenant existente. A checagem de
 * existência do Tenant é o mesmo contrato de `RegisterIdentity` —
 * nunca confiar que o ID recebido corresponde a algo real.
 *
 * Sobre a ausência de evento de domínio, ver {@see CreateTenant}.
 */
final class CreateCompany
{
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly CompanyRepository $companies,
    ) {
    }

    public function execute(TenantId $tenantId, string $name): Company
    {
        if ($this->tenants->find($tenantId) === null) {
            throw new SigmaException('Tenant não encontrado.', 'identity.tenant_not_found');
        }

        $company = new Company(CompanyId::generate(), $tenantId, $name);
        $this->companies->save($company);

        return $company;
    }
}
