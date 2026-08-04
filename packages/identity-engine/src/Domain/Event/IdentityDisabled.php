<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;

final class IdentityDisabled implements DomainEvent
{
    public function __construct(
        public readonly IdentityId $identityId,
        public readonly string $reason,
    ) {
    }

    public function name(): string
    {
        return 'identity.disabled';
    }

    public function payload(): array
    {
        return [
            'identityId' => $this->identityId->toString(),
            'reason' => $this->reason,
        ];
    }
}
