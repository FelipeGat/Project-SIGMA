<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

enum ApprovalDecisionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
