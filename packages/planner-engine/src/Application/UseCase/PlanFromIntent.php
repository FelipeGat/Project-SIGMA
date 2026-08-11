<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\PlannerEngine\Domain\Event\MissionPlanned;
use Sigma\PlannerEngine\Domain\Event\PlanningFailed;
use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\Plan;
use Sigma\PlannerEngine\Domain\Planner;
use Sigma\PlannerEngine\Domain\PlanningFailure;

/**
 * O único caso de uso do Planner Engine: recebe uma Intent, decide, e
 * publica o desfecho.
 *
 * Não persiste nada e não devolve Mission alguma — o Planner publica e
 * esquece (ADR-0095/ADR-0089). Quem cria a Mission é o Mission Engine,
 * reagindo a `mission.planned`.
 */
final class PlanFromIntent
{
    public function __construct(
        private readonly Planner $planner,
        private readonly IEventBus $eventBus,
    ) {
    }

    /**
     * Devolve o desfecho para quem chamou poder responder à requisição
     * — nunca para ser guardado.
     */
    public function execute(Intent $intent): Plan|PlanningFailure
    {
        $outcome = $this->planner->plan($intent);

        $event = $outcome instanceof Plan
            ? new MissionPlanned($intent, $outcome)
            : new PlanningFailed($intent, $outcome);

        $this->eventBus->publish($event->name(), $event->payload());

        return $outcome;
    }
}
