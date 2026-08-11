<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Uma fase de um `PlanTemplate`, traduzida 1:1 em `SubtaskCandidate`
 * no momento de planejar.
 *
 * `requiredAutonomyLevel` é como um Playbook expressa "ponto de
 * decisão humana": o Mission Engine cria um `ApprovalGate` quando o
 * nível exigido supera o `autonomyCeiling` da Mission
 * (`Mission::advanceToNextSubtask()`, Release 5B). O Planner **marca**
 * o nível; nunca compara, nunca cria gate.
 */
final class PlanStep
{
    public function __construct(
        public readonly string $description,
        public readonly int $requiredAutonomyLevel,
        public readonly ?string $candidateAgent = null,
        public readonly ?string $candidateCapability = null,
    ) {
        if (trim($description) === '') {
            throw new SigmaException('PlanStep.description não pode ser vazio.', 'planner.invalid_plan_step');
        }

        if ($requiredAutonomyLevel < 0 || $requiredAutonomyLevel > 3) {
            throw new SigmaException('PlanStep.requiredAutonomyLevel precisa estar entre 0 e 3.', 'planner.invalid_autonomy_level');
        }
    }

    public function toCandidate(): SubtaskCandidate
    {
        return new SubtaskCandidate(
            $this->description,
            $this->candidateAgent,
            $this->candidateCapability,
            $this->requiredAutonomyLevel,
        );
    }
}
