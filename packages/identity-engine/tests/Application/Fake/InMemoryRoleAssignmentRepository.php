<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\RoleAssignmentRepository;
use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;

final class InMemoryRoleAssignmentRepository implements RoleAssignmentRepository
{
    /** @var array<string, RoleAssignment> */
    private array $assignments = [];

    public function save(RoleAssignment $assignment): void
    {
        $this->assignments[$this->key($assignment->role()->id(), $assignment->subjectType(), $assignment->subjectId(), $assignment->scope())] = $assignment;
    }

    public function findExact(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): ?RoleAssignment
    {
        return $this->assignments[$this->key($roleId, $subjectType, $subjectId, $scope)] ?? null;
    }

    public function findForUserAndTeams(TenantId $tenantId, UserId $userId, array $teamIds): array
    {
        return array_values(array_filter($this->assignments, function (RoleAssignment $assignment) use ($tenantId, $userId, $teamIds): bool {
            if ($assignment->isRevoked() || !$assignment->role()->tenantId()->equals($tenantId)) {
                return false;
            }

            if ($assignment->subjectType() === SubjectType::User) {
                return $assignment->subjectId()->equals($userId);
            }

            foreach ($teamIds as $teamId) {
                if ($assignment->subjectId()->equals($teamId)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function key(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): string
    {
        return implode('|', [
            $roleId->toString(),
            $subjectType->value,
            $subjectId->toString(),
            $scope->type->value,
            $scope->id->toString(),
        ]);
    }
}
