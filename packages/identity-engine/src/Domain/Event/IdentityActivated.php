<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;

final class IdentityActivated implements DomainEvent
{
    public function __construct(public readonly IdentityId $identityId)
    {
    }

    public function name(): string
    {
        return 'identity.activated';
    }

    public function payload(): array
    {
        return ['identityId' => $this->identityId->toString()];
    }
}
