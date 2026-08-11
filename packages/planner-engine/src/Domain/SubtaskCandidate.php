<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * A forma candidata de uma Subtask, produzida pelo Planner.
 *
 * **Mesma forma** que `Sigma\MissionEngine\Domain\SubtaskCandidate`,
 * deliberadamente **não** o mesmo código (ADR-0096): Planner e Mission
 * são bounded contexts distintos que trocam dados na fronteira. Aqui,
 * um candidato é *o que o Planner decidiu*; lá, é *o que o Mission
 * Engine recebeu e vai executar*.
 *
 * A igualdade das duas formas é garantida por
 * contracts/Planner.contract.yaml e por teste automatizado — nunca
 * pelo compilador. Se divergirem, o teste que leva o payload até
 * `CreateMission` sem adaptação é o que quebra.
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
            throw new SigmaException('SubtaskCandidate.description não pode ser vazio.', 'planner.invalid_subtask_candidate');
        }

        if ($requiredAutonomyLevel < 0 || $requiredAutonomyLevel > 3) {
            throw new SigmaException('requiredAutonomyLevel precisa estar entre 0 e 3.', 'planner.invalid_autonomy_level');
        }
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'description' => $this->description,
            'candidateAgent' => $this->candidateAgent,
            'candidateCapability' => $this->candidateCapability,
            'requiredAutonomyLevel' => $this->requiredAutonomyLevel,
        ];
    }
}
