<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\CompanyRepository;
use Sigma\IdentityEngine\Application\WorkspaceRepository;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;

/**
 * Cria um Workspace dentro de uma Company existente — a unidade de
 * contexto operacional que `SelectWorkspace` passa a poder selecionar
 * e que toda Mission carrega como escopo.
 *
 * Sobre a ausência de evento de domínio, ver {@see CreateTenant}.
 */
final class CreateWorkspace
{
    public function __construct(
        private readonly CompanyRepository $companies,
        private readonly WorkspaceRepository $workspaces,
    ) {
    }

    public function execute(CompanyId $companyId, string $name): Workspace
    {
        if ($this->companies->find($companyId) === null) {
            throw new SigmaException('Company não encontrada.', 'identity.company_not_found');
        }

        $workspace = new Workspace(WorkspaceId::generate(), $companyId, $name);
        $this->workspaces->save($workspace);

        return $workspace;
    }
}
