<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\SessionId;

final class SessionStarted implements DomainEvent
{
    public function __construct(
        public readonly SessionId $sessionId,
        public readonly IdentityId $identityId,
        public readonly \DateTimeImmutable $issuedAt,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
    }

    public function name(): string
    {
        return 'session.started';
    }

    public function payload(): array
    {
        return [
            'sessionId' => $this->sessionId->toString(),
            'identityId' => $this->identityId->toString(),
            'issuedAt' => $this->issuedAt->format(DATE_ATOM),
            'expiresAt' => $this->expiresAt->format(DATE_ATOM),
        ];
    }
}
