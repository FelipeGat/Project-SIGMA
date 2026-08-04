<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;

/**
 * Adicionado por simetria com RoleAssigned — não estava na lista
 * original do Product Owner, mas todo RoleAssignment que pode ser
 * criado precisa poder ser desfeito (ver DOMAIN_EVENTS.md).
 */
final class RoleRevoked implements DomainEvent
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly SubjectType $subjectType,
        public readonly Identifier $subjectId,
        public readonly Scope $scope,
    ) {
    }

    public function name(): string
    {
        return 'role.revoked';
    }

    public function payload(): array
    {
        return [
            'roleId' => $this->roleId->toString(),
            'subjectType' => $this->subjectType->value,
            'subjectId' => $this->subjectId->toString(),
            'scopeType' => $this->scope->type->value,
            'scopeId' => $this->scope->id->toString(),
        ];
    }
}
