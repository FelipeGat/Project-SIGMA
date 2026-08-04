<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\RoleAssignmentRepository;
use Sigma\IdentityEngine\Domain\Identifier;
use Sigma\IdentityEngine\Domain\RoleId;
use Sigma\IdentityEngine\Domain\Scope;
use Sigma\IdentityEngine\Domain\SubjectType;
use Sigma\Kernel\Contract\IEventBus;

final class RevokeRole
{
    public function __construct(
        private readonly RoleAssignmentRepository $roleAssignments,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(RoleId $roleId, SubjectType $subjectType, Identifier $subjectId, Scope $scope): void
    {
        $assignment = $this->roleAssignments->findExact($roleId, $subjectType, $subjectId, $scope);
        if ($assignment === null) {
            throw new SigmaException('RoleAssignment não encontrado.', 'identity.role_assignment_not_found');
        }

        $assignment->revoke();
        $this->roleAssignments->save($assignment);

        foreach ($assignment->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
