<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Domain\Event\SessionEnded;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\Kernel\Contract\IEventBus;

final class Logout
{
    public function __construct(
        private readonly IdentityRepository $identities,
        private readonly SessionRepository $sessions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(SessionId $sessionId): void
    {
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            throw new SigmaException('Session não encontrada.', 'identity.session_not_found');
        }

        $identity = $this->identities->find($session->identityId());
        if ($identity === null) {
            throw new SigmaException('Identity não encontrada.', 'identity.identity_not_found');
        }

        $identity->endSession($session, SessionEnded::REASON_LOGOUT);
        $this->sessions->delete($session->id());

        foreach ($identity->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
