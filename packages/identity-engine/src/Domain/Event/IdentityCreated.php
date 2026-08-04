<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;

final class IdentityCreated implements DomainEvent
{
    public function __construct(
        public readonly IdentityId $identityId,
        public readonly UserId $userId,
        public readonly TenantId $tenantId,
    ) {
    }

    public function name(): string
    {
        return 'identity.created';
    }

    public function payload(): array
    {
        return [
            'identityId' => $this->identityId->toString(),
            'userId' => $this->userId->toString(),
            'tenantId' => $this->tenantId->toString(),
        ];
    }
}
