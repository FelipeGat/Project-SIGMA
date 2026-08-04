<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\ApprovalGateId;
use Sigma\MissionEngine\Domain\MissionId;

final class MissionApproved implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly ApprovalGateId $approvalGateId,
        public readonly string $decidedBy,
    ) {
    }

    public function name(): string
    {
        return 'mission.approved';
    }

    public function payload(): array
    {
        return [
            'missionId' => $this->missionId->toString(),
            'approvalGateId' => $this->approvalGateId->toString(),
            'decidedBy' => $this->decidedBy,
        ];
    }
}
