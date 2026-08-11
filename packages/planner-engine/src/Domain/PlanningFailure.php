<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * O Planner tem o direito de não saber planejar (ADR-0097). Isto é o
 * desfecho alternativo — nunca uma exceção engolida, nunca um Plan
 * vazio.
 */
final class PlanningFailure
{
    public function __construct(
        public readonly IntentId $intentId,
        public readonly CorrelationId $correlationId,
        public readonly PlanningFailureReason $reason,
        public readonly string $detail,
    ) {
        if (trim($detail) === '') {
            throw new SigmaException('PlanningFailure.detail não pode ser vazio.', 'planner.invalid_failure');
        }
    }
}
