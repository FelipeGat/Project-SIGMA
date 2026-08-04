<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\SessionId;

final class SessionEnded implements DomainEvent
{
    public const REASON_LOGOUT = 'logout';
    public const REASON_EXPIRED = 'expired';

    public function __construct(
        public readonly SessionId $sessionId,
        public readonly IdentityId $identityId,
        public readonly string $reason,
    ) {
    }

    public function name(): string
    {
        return 'session.ended';
    }

    public function payload(): array
    {
        return [
            'sessionId' => $this->sessionId->toString(),
            'identityId' => $this->identityId->toString(),
            'reason' => $this->reason,
        ];
    }
}
