<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain\Event;

use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\PlanningFailure;

/**
 * A Intent foi entendida e nenhum Plan pôde ser produzido (ADR-0097).
 *
 * NÃO confundir com `IntentRejected` (Intent Engine, Release 7):
 * aquele significa "não entendi o pedido"; este, "entendi e não sei
 * como executá-lo". São diagnósticos opostos para quem investiga.
 */
final class PlanningFailed implements DomainEvent
{
    public function __construct(
        public readonly Intent $intent,
        public readonly PlanningFailure $failure,
    ) {
    }

    public function name(): string
    {
        return 'planning.failed';
    }

    public function payload(): array
    {
        return [
            'intentId' => $this->failure->intentId->toString(),
            'correlationId' => $this->failure->correlationId->toString(),
            'tenantId' => $this->intent->tenantId->toString(),
            'workspaceId' => $this->intent->workspaceId?->toString(),
            'reason' => $this->failure->reason->value,
            'detail' => $this->failure->detail,
        ];
    }
}
