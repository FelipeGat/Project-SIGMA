<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

enum ActorType: string
{
    case User = 'user';
    case System = 'system';
    case Agent = 'agent';
}
