<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\IdentityEngine\Domain\Event\RoleAssigned;
use Sigma\IdentityEngine\Domain\Event\RoleRevoked;

/**
 * Um Role atribuído a um User ou Team, num escopo específico —
 * IDENTITY_MODEL.md#relações. Identificado pela combinação
 * (role, subject, scope), não por um id próprio.
 */
final class RoleAssignment
{
    use RecordsDomainEvents;

    private bool $revoked = false;

    private function __construct(
        private readonly Role $role,
        private readonly SubjectType $subjectType,
        private readonly Identifier $subjectId,
        private readonly Scope $scope,
    ) {
    }

    /** Reidrata um RoleAssignment já existente a partir de dados persistidos (Infrastructure, Release 3B). */
    public static function reconstitute(Role $role, SubjectType $subjectType, Identifier $subjectId, Scope $scope, bool $revoked): self
    {
        $assignment = new self($role, $subjectType, $subjectId, $scope);
        $assignment->revoked = $revoked;

        return $assignment;
    }

    public static function assign(Role $role, SubjectType $subjectType, Identifier $subjectId, Scope $scope): self
    {
        $assignment = new self($role, $subjectType, $subjectId, $scope);
        $assignment->record(new RoleAssigned($role->id(), $subjectType, $subjectId, $scope));

        return $assignment;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function subjectType(): SubjectType
    {
        return $this->subjectType;
    }

    public function subjectId(): Identifier
    {
        return $this->subjectId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): void
    {
        if ($this->revoked) {
            return;
        }

        $this->revoked = true;
        $this->record(new RoleRevoked($this->role->id(), $this->subjectType, $this->subjectId, $this->scope));
    }

    /** Este assignment concede algo dentro do escopo de $workspace/$company/$tenant? */
    public function appliesToScope(Scope $target): bool
    {
        return $this->scope->equals($target);
    }
}
