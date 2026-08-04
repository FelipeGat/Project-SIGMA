<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\RoleAssignmentRepository;
use Sigma\IdentityEngine\Application\RoleRepository;
use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleAssignment;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\Kernel\Contract\IEventBus;

final class AssignRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): RoleAssignment
    {
        $role = $this->roles->find($roleId);
        if ($role === null) {
            throw new SigmaException('Role não encontrado.', 'identity.role_not_found');
        }

        $assignment = RoleAssignment::assign($role, $subjectType, $subjectId, $scope);
        $this->roleAssignments->save($assignment);

        foreach ($assignment->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $assignment;
    }
}
