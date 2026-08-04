<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class WorkspaceSelected implements DomainEvent
{
    public function __construct(
        public readonly SessionId $sessionId,
        public readonly IdentityId $identityId,
        public readonly WorkspaceId $workspaceId,
    ) {
    }

    public function name(): string
    {
        return 'workspace.selected';
    }

    public function payload(): array
    {
        return [
            'sessionId' => $this->sessionId->toString(),
            'identityId' => $this->identityId->toString(),
            'workspaceId' => $this->workspaceId->toString(),
        ];
    }
}
