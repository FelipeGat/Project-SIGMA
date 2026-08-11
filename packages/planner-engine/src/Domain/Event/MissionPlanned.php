<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain\Event;

use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\Plan;

/**
 * O Plan foi produzido. É o único caminho pelo qual uma Mission passa
 * a existir a partir de um planejamento real (ADR-0089).
 *
 * O payload carrega tudo que o Mission Engine precisa para criar a
 * Mission sem consultar ninguém — inclusive `correlationId`, que
 * `Mission::create()` exige desde a Release 5B. Sem ele o consumidor
 * teria que gerar um novo, quebrando a rastreabilidade fim-a-fim
 * exatamente na fronteira entre decidir e executar.
 */
final class MissionPlanned implements DomainEvent
{
    public function __construct(
        public readonly Intent $intent,
        public readonly Plan $plan,
    ) {
    }

    public function name(): string
    {
        return 'mission.planned';
    }

    public function payload(): array
    {
        return [
            'intentId' => $this->intent->id->toString(),
            'correlationId' => $this->intent->correlationId->toString(),
            'tenantId' => $this->intent->tenantId->toString(),
            'workspaceId' => $this->intent->workspaceId?->toString(),
            'objective' => $this->intent->objective,
            'autonomyCeiling' => $this->intent->autonomyCeiling,
            'actor' => [
                'type' => $this->intent->actor->type->value,
                'id' => $this->intent->actor->id,
            ],
            'plan' => $this->plan->toPayload(),
        ];
    }
}
