<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * A forma "candidata" de uma Subtask dentro de um Plan — antes de a
 * Mission a aceitar como Subtask em execução (ver ARCHITECTURE.md §5,
 * MISSION_MODEL.md#plan). `requiredAutonomyLevel` é o que
 * `Mission::evaluateApproval()` compara contra o `autonomyCeiling` da
 * Mission — resolvido por quem monta o Plan (Planner Engine ou, nesta
 * Release, quem chama manualmente), nunca pelo Mission Engine.
 */
final class SubtaskCandidate
{
    public function __construct(
        public readonly string $description,
        public readonly ?string $candidateAgent,
        public readonly ?string $candidateCapability,
        public readonly int $requiredAutonomyLevel,
    ) {
        if (trim($description) === '') {
            throw new SigmaException('SubtaskCandidate.description não pode ser vazio.', 'mission.invalid_subtask_candidate');
        }

        if ($requiredAutonomyLevel < 0 || $requiredAutonomyLevel > 3) {
            throw new SigmaException('requiredAutonomyLevel precisa estar entre 0 e 3.', 'mission.invalid_autonomy_level');
        }
    }
}
