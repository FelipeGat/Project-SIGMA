<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\IntentId;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

/**
 * Traduz o payload de `mission.planned` — publicado pelo Planner
 * Engine — numa Mission real.
 *
 * **É a primeira vez que o Mission Engine consome um evento.** Até a
 * Release 5C ele apenas publicava (ver Proposal 5C: "nenhum evento é
 * assinado").
 *
 * O acoplamento com o Planner é só a forma do payload, nunca código:
 * este pacote não importa `sigma/planner-engine` e não sabe que classe
 * produziu o evento (ADR-0092/ADR-0096). Este método é o ponto exato
 * onde as duas representações de `Plan` se encontram — se divergirem,
 * é aqui que quebra.
 */
final class CreateMissionFromPlannedEvent
{
    public function __construct(private readonly CreateMission $createMission)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload, \DateTimeImmutable $now): Mission
    {
        return $this->createMission->execute(
            TenantId::fromString($this->requireString($payload, 'tenantId')),
            isset($payload['workspaceId']) && $payload['workspaceId'] !== null
                ? WorkspaceId::fromString((string) $payload['workspaceId'])
                : null,
            CorrelationId::fromString($this->requireString($payload, 'correlationId')),
            isset($payload['intentId']) && $payload['intentId'] !== null
                ? IntentId::fromString((string) $payload['intentId'])
                : null,
            $this->requireString($payload, 'objective'),
            $this->planFrom($payload),
            $this->actorFrom($payload),
            (int) ($payload['autonomyCeiling'] ?? 0),
            $now,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function planFrom(array $payload): Plan
    {
        $raw = $payload['plan'] ?? null;
        if (!is_array($raw) || !is_array($raw['subtaskCandidates'] ?? null) || $raw['subtaskCandidates'] === []) {
            throw new SigmaException(
                'Evento mission.planned sem plan.subtaskCandidates — um Plan vazio deveria ter sido planning.failed.',
                'mission.invalid_planned_event',
            );
        }

        $candidates = [];
        foreach ($raw['subtaskCandidates'] as $candidate) {
            if (!is_array($candidate)) {
                throw new SigmaException('plan.subtaskCandidates malformado.', 'mission.invalid_planned_event');
            }

            $candidates[] = new SubtaskCandidate(
                (string) ($candidate['description'] ?? ''),
                isset($candidate['candidateAgent']) ? (string) $candidate['candidateAgent'] : null,
                isset($candidate['candidateCapability']) ? (string) $candidate['candidateCapability'] : null,
                (int) ($candidate['requiredAutonomyLevel'] ?? 0),
            );
        }

        // `source` vem do evento, não é assumido: um Plan que chegue
        // marcado como `manual` por este caminho é um erro de quem
        // publicou, e o enum recusa qualquer outro valor.
        $source = PlanSource::tryFrom((string) ($raw['source'] ?? ''));
        if ($source === null) {
            throw new SigmaException(
                sprintf('plan.source desconhecido: "%s".', (string) ($raw['source'] ?? '')),
                'mission.invalid_planned_event',
            );
        }

        return new Plan($candidates, $source);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function actorFrom(array $payload): Actor
    {
        $raw = $payload['actor'] ?? null;
        if (!is_array($raw)) {
            throw new SigmaException('Evento mission.planned sem actor.', 'mission.invalid_planned_event');
        }

        $type = ActorType::tryFrom((string) ($raw['type'] ?? ''));
        if ($type === null) {
            throw new SigmaException(
                sprintf('actor.type desconhecido: "%s".', (string) ($raw['type'] ?? '')),
                'mission.invalid_planned_event',
            );
        }

        return new Actor($type, (string) ($raw['id'] ?? ''));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new SigmaException(
                sprintf('Evento mission.planned sem "%s".', $key),
                'mission.invalid_planned_event',
            );
        }

        return $value;
    }
}
