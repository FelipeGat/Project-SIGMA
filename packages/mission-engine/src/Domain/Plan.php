<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * O plano recebido como entrada — nunca produzido pelo Mission Engine
 * (ADR-0092). `source: Manual` é o único valor possível enquanto o
 * Planner Engine (Release 6) não existir.
 */
final class Plan
{
    /** @param list<SubtaskCandidate> $subtaskCandidates */
    public function __construct(
        public readonly array $subtaskCandidates,
        public readonly PlanSource $source,
    ) {
        if ($this->subtaskCandidates === []) {
            throw new SigmaException('Um Plan precisa de pelo menos uma Subtask candidata.', 'mission.empty_plan');
        }

        foreach ($this->subtaskCandidates as $candidate) {
            if (!$candidate instanceof SubtaskCandidate) {
                throw new SigmaException('Plan.subtaskCandidates só aceita SubtaskCandidate.', 'mission.invalid_plan');
            }
        }
    }
}
