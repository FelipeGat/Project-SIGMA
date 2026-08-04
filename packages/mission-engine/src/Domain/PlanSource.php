<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * `Manual` é o único valor possível enquanto o Planner Engine
 * (Release 6) não existir — ver ADR-0031/ADR-0092.
 */
enum PlanSource: string
{
    case Planner = 'planner';
    case Manual = 'manual';
}
