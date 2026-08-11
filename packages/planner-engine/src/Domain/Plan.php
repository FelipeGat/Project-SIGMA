<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * O que o Planner produz. Mesma forma do `Plan` do Mission Engine,
 * código separado (ADR-0096).
 *
 * Nunca vazio: um plano sem passos é `PlanningFailure` com razão
 * `EmptyPlan`, não um Plan degenerado (ADR-0097).
 */
final class Plan
{
    /** @param list<SubtaskCandidate> $subtaskCandidates */
    public function __construct(
        public readonly array $subtaskCandidates,
        public readonly PlanSource $source,
    ) {
        if ($this->subtaskCandidates === []) {
            throw new SigmaException('Um Plan precisa de pelo menos uma Subtask candidata.', 'planner.empty_plan');
        }

        foreach ($this->subtaskCandidates as $candidate) {
            if (!$candidate instanceof SubtaskCandidate) {
                throw new SigmaException('Plan.subtaskCandidates só aceita SubtaskCandidate.', 'planner.invalid_plan');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'source' => $this->source->value,
            'subtaskCandidates' => array_map(
                static fn (SubtaskCandidate $c): array => $c->toPayload(),
                $this->subtaskCandidates,
            ),
        ];
    }
}
