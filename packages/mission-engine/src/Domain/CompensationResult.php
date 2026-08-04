<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

enum CompensationResult: string
{
    case Compensated = 'compensated';
    case CompensationFailed = 'compensation_failed';
}
