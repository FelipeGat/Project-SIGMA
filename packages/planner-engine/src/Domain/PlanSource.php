<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

/**
 * Mesma forma do enum homônimo de `packages/mission-engine`
 * (ADR-0096) — código separado, valores idênticos. `Manual` existe
 * aqui apenas para que a forma seja a mesma; o Planner nunca produz um
 * Plan com esse valor.
 */
enum PlanSource: string
{
    case Planner = 'planner';
    case Manual = 'manual';
}
