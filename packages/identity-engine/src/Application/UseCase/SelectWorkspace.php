<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Application\WorkspaceRepository;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\Kernel\Contract\IEventBus;

final class SelectWorkspace
{
    public function __construct(
        private readonly IdentityRepository $identities,
        private readonly WorkspaceRepository $workspaces,
        private readonly SessionRepository $sessions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(SessionId $sessionId, WorkspaceId $workspaceId, \DateTimeImmutable $now): Session
    {
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new SigmaException('Session não encontrada.', 'identity.session_not_found');
        }

        $identity = $this->identities->find($session->identityId());
        if ($identity === null) {
            throw new SigmaException('Identity não encontrada.', 'identity.identity_not_found');
        }

        $workspace = $this->workspaces->find($workspaceId);
        if ($workspace === null) {
            throw new SigmaException('Workspace não encontrado.', 'identity.workspace_not_found');
        }

        $updated = $identity->selectWorkspace($session, $workspace, $now);
        $this->sessions->save($updated);

        foreach ($identity->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $updated;
    }
}
