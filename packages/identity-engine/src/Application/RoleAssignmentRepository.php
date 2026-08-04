<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\TeamId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;

interface RoleAssignmentRepository
{
    public function save(RoleAssignment $assignment): void;

    public function findExact(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): ?RoleAssignment;

    /**
     * Todos os RoleAssignments não revogados aplicáveis a este User
     * (direto) ou a qualquer um destes Teams, dentro do Tenant — o
     * filtro de escopo (Workspace/Company/Tenant) é feito depois, em
     * Identity::resolveContext(), não aqui.
     *
     * @param list<TeamId> $teamIds
     * @return list<RoleAssignment>
     */
    public function findForUserAndTeams(TenantId $tenantId, UserId $userId, array $teamIds): array;
}
