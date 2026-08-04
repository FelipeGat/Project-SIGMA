<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

enum SubtaskStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Executing = 'executing';
    case Validated = 'validated';
    case Failed = 'failed';
    case Compensated = 'compensated';
    case Skipped = 'skipped';
}
