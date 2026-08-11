<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * A tradução executável de um Playbook — o conhecimento do Planner
 * sobre como planejar um tipo recorrente de Mission.
 *
 * `playbook` é obrigatório e não decorativo: nenhuma regra de
 * planejamento existe sem alguém de negócio ter escrito por que ela é
 * assim (PLANNER_MANIFESTO.md). Nesta Release os templates são código
 * e o campo é um vínculo documental verificável por revisão — não é
 * lido nem validado em runtime.
 */
final class PlanTemplate
{
    /**
     * @param list<string> $requiredParameters
     * @param list<PlanStep> $steps
     */
    public function __construct(
        public readonly IntentKind $kind,
        public readonly string $playbook,
        public readonly array $requiredParameters,
        public readonly array $steps,
    ) {
        if (trim($playbook) === '') {
            throw new SigmaException('PlanTemplate.playbook é obrigatório.', 'planner.invalid_plan_template');
        }
    }

    /** @return list<string> Os parâmetros que a Intent não trouxe. */
    public function missingParameters(Intent $intent): array
    {
        return array_values(array_filter(
            $this->requiredParameters,
            static fn (string $name): bool => !$intent->hasParameter($name),
        ));
    }

    public function toPlan(): Plan
    {
        return new Plan(
            array_map(static fn (PlanStep $step): SubtaskCandidate => $step->toCandidate(), $this->steps),
            PlanSource::Planner,
        );
    }
}
