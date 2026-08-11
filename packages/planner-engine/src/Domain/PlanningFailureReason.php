<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

/**
 * As três causas pelas quais o Planner entende o pedido e ainda assim
 * não consegue planejá-lo (ADR-0097). Verificadas **em ordem** por
 * `Planner::plan()`; a primeira que dispara encerra — ver
 * PLANNER_LIFECYCLE.md.
 */
enum PlanningFailureReason: string
{
    case UnknownIntentKind = 'unknown_intent_kind';
    case MissingRequiredParameter = 'missing_required_parameter';
    case EmptyPlan = 'empty_plan';
}
