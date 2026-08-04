<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\ApprovalGateId;
use Sigma\MissionEngine\Domain\MissionId;

final class MissionApprovalRequested implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly ApprovalGateId $approvalGateId,
        public readonly string $reason,
    ) {
    }

    public function name(): string
    {
        return 'mission.approval_requested';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'approvalGateId' => $this->approvalGateId->toString(),
            'reason' => $this->reason,
        ];
    }
}
