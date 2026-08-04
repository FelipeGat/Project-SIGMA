<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\CompanyRepository;
use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\RoleAssignmentRepository;
use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Application\TeamRepository;
use Sigma\IdentityEngine\Application\WorkspaceRepository;
use Sigma\IdentityEngine\Domain\Context;
use Sigma\IdentityEngine\Domain\SessionId;

/** Identity Lifecycle etapas 6-8 — Permissions/Autonomy resolvidas, Identity pronta. */
final class ResolveContext
{
    public function __construct(
        private readonly IdentityRepository $identities,
        private readonly WorkspaceRepository $workspaces,
        private readonly CompanyRepository $companies,
        private readonly TeamRepository $teams,
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly SessionRepository $sessions,
    ) {
    }

    public function execute(SessionId $sessionId, \DateTimeImmutable $now): Context
    {
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new SigmaException('Session não encontrada.', 'identity.session_not_found');
        }

        $identity = $this->identities->find($session->identityId());
        if ($identity === null) {
            throw new SigmaException('Identity não encontrada.', 'identity.identity_not_found');
        }

        $workspaceId = $session->workspaceId();
        if ($workspaceId === null) {
            throw new SigmaException('Esta Session ainda não selecionou um Workspace.', 'identity.workspace_not_selected');
        }

        $workspace = $this->workspaces->find($workspaceId);
        if ($workspace === null) {
            throw new SigmaException('Workspace não encontrado.', 'identity.workspace_not_found');
        }

        $company = $this->companies->find($workspace->companyId());
        if ($company === null) {
            throw new SigmaException('Company não encontrada.', 'identity.company_not_found');
        }

        $teams = $this->teams->findByCompany($company->id());
        $teamIds = array_map(static fn ($team) => $team->id(), $teams);

        $roleAssignments = $this->roleAssignments->findForUserAndTeams($identity->tenant()->id(), $identity->user()->id(), $teamIds);

        return $identity->resolveContext($session, $workspace, $company, $roleAssignments, $teams, $now);
    }
}
