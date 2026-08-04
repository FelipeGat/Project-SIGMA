<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\RoleAssignmentRepository;
use Sigma\IdentityEngine\Application\RoleRepository;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\ScopeType;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\TeamId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class PdoRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly RoleRepository $roles,
    ) {
    }

    public function save(RoleAssignment $assignment): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO role_assignments (role_id, subject_type, subject_id, scope_type, scope_id, revoked, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE revoked = VALUES(revoked)',
        );
        $statement->execute([
            $assignment->role()->id()->toString(),
            $assignment->subjectType()->value,
            $assignment->subjectId()->toString(),
            $assignment->scope()->type->value,
            $assignment->scope()->id->toString(),
            $assignment->isRevoked() ? 1 : 0,
        ]);
    }

    public function findExact(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): ?RoleAssignment
    {
        $statement = $this->pdo->prepare(
            'SELECT role_id, subject_type, subject_id, scope_type, scope_id, revoked
             FROM role_assignments
             WHERE role_id = ? AND subject_type = ? AND subject_id = ? AND scope_type = ? AND scope_id = ?',
        );
        $statement->execute([
            $roleId->toString(),
            $subjectType->value,
            $subjectId->toString(),
            $scope->type->value,
            $scope->id->toString(),
        ]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    public function findForUserAndTeams(TenantId $tenantId, UserId $userId, array $teamIds): array
    {
        $teamIdValues = array_map(static fn (TeamId $id): string => $id->toString(), $teamIds);
        $placeholders = implode(',', array_fill(0, count($teamIdValues), '?'));

        $sql = 'SELECT ra.role_id, ra.subject_type, ra.subject_id, ra.scope_type, ra.scope_id, ra.revoked
                FROM role_assignments ra
                INNER JOIN roles r ON r.id = ra.role_id
                WHERE r.tenant_id = ?
                  AND ra.revoked = 0
                  AND (
                    (ra.subject_type = ? AND ra.subject_id = ?)'
                    . ($teamIdValues === [] ? '' : " OR (ra.subject_type = ? AND ra.subject_id IN ({$placeholders}))")
                    . ')';

        $params = [$tenantId->toString(), SubjectType::User->value, $userId->toString()];
        if ($teamIdValues !== []) {
            $params[] = SubjectType::Team->value;
            array_push($params, ...$teamIdValues);
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $assignments = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $assignment = $this->hydrate($row);
            if ($assignment !== null) {
                $assignments[] = $assignment;
            }
        }

        return $assignments;
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate($row): ?RoleAssignment
    {
        if ($row === false) {
            return null;
        }

        $role = $this->roles->find(RoleId::fromString($row['role_id']));
        if ($role === null) {
            return null;
        }

        $subjectType = SubjectType::from($row['subject_type']);
        $subjectId = $subjectType === SubjectType::User
            ? UserId::fromString($row['subject_id'])
            : TeamId::fromString($row['subject_id']);

        $scope = $this->hydrateScope(ScopeType::from($row['scope_type']), $row['scope_id']);

        return RoleAssignment::reconstitute($role, $subjectType, $subjectId, $scope, (bool) $row['revoked']);
    }

    private function hydrateScope(ScopeType $type, string $id): Scope
    {
        return match ($type) {
            ScopeType::Tenant => Scope::tenant(TenantId::fromString($id)),
            ScopeType::Company => Scope::company(CompanyId::fromString($id)),
            ScopeType::Workspace => Scope::workspace(WorkspaceId::fromString($id)),
        };
    }
}
