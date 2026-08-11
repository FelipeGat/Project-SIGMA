<?php

declare(strict_types=1);

namespace Sigma\Auth;

use Sigma\IdentityEngine\Application\UseCase\CreateCompany;
use Sigma\IdentityEngine\Application\UseCase\CreateTenant;
use Sigma\IdentityEngine\Application\UseCase\CreateWorkspace;
use Sigma\IdentityEngine\Application\UseCase\RegisterIdentity;
use Sigma\Kernel\Contract\IContainer;

/**
 * Cria a hierarquia mínima operável do SIGMA — Tenant → Company →
 * Workspace → User com credencial — compondo os casos de uso do
 * Identity Engine, nunca `INSERT` direto.
 *
 * Separado do binário (`bin/bootstrap-identity.php`) para ser testável
 * sem executar um processo CLI, mesmo padrão de `AuthEndpoints` em
 * relação a `public/index.php`.
 *
 * Deliberadamente CLI e não endpoint HTTP (ver Proposal 5.5): quem
 * pode se registrar, sob qual convite e com qual verificação é decisão
 * de produto que esta Release não tem contexto para tomar.
 */
final class IdentityBootstrapper
{
    public function __construct(private readonly IContainer $container)
    {
    }

    /**
     * @return array{tenantId: string, companyId: string, workspaceId: string, identityId: string, userId: string}
     */
    public function execute(
        string $tenantName,
        string $companyName,
        string $workspaceName,
        string $userName,
        string $email,
        string $plainPassword,
    ): array {
        /** @var CreateTenant $createTenant */
        $createTenant = $this->container->get(CreateTenant::class);
        /** @var CreateCompany $createCompany */
        $createCompany = $this->container->get(CreateCompany::class);
        /** @var CreateWorkspace $createWorkspace */
        $createWorkspace = $this->container->get(CreateWorkspace::class);
        /** @var RegisterIdentity $registerIdentity */
        $registerIdentity = $this->container->get(RegisterIdentity::class);

        $tenant = $createTenant->execute($tenantName);
        $company = $createCompany->execute($tenant->id(), $companyName);
        $workspace = $createWorkspace->execute($company->id(), $workspaceName);
        $identity = $registerIdentity->execute($tenant->id(), $userName, $email, $plainPassword);

        return [
            'tenantId' => $tenant->id()->toString(),
            'companyId' => $company->id()->toString(),
            'workspaceId' => $workspace->id()->toString(),
            'identityId' => $identity->id()->toString(),
            'userId' => $identity->user()->id()->toString(),
        ];
    }
}
