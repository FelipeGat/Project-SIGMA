<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/** Mesma forma do campo `actor` do Envelope (SIGMA_PROTOCOL.md §1). */
enum ActorType: string
{
    case User = 'user';
    case System = 'system';
    case Agent = 'agent';
}
