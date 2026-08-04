<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

use Sigma\MissionEngine\Domain\ApprovalGateId;
use Sigma\MissionEngine\Domain\MissionId;

/**
 * Rejeição de um ApprovalGate de uma Mission já criada — nunca
 * confundir com `IntentRejected` (Intent Engine, antes de a Mission
 * existir, ver MISSION_EVENTS.md).
 */
final class MissionRejected implements DomainEvent
{
    public function __construct(
        public readonly MissionId $missionId,
        public readonly ApprovalGateId $approvalGateId,
        public readonly string $decidedBy,
    ) {
    }

    public function name(): string
    {
        return 'mission.rejected';
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
