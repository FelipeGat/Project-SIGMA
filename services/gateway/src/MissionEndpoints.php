<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\Core\Envelope;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\Kernel\Contract\IContainer;
use Sigma\MissionEngine\Application\UseCase\CreateMission;
use Sigma\MissionEngine\Application\UseCase\GetMission;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

/**
 * Os dois endpoints de Mission — a primeira superfície HTTP de domínio
 * do SIGMA (Release 5.5). Separada do front controller
 * (`public/index.php`) para ser testável sem subir um servidor HTTP,
 * mesmo padrão de {@see HealthEndpoints} e de `AuthEndpoints`.
 *
 * `tenantId`/`workspaceId` nunca vêm do corpo da requisição — são
 * resolvidos da Session via `ResolveContext`, para que um requisitante
 * não consiga criar Mission no escopo de outro Tenant apenas mudando
 * o JSON que envia.
 *
 * @see public/index.php
 */
final class MissionEndpoints
{
    public function __construct(private readonly IContainer $container)
    {
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function create(?string $sessionToken, array $body): array
    {
        return $this->guarded(function () use ($sessionToken, $body) {
            $context = $this->resolveContext($sessionToken);

            $objective = trim((string) ($body['objective'] ?? ''));
            if ($objective === '') {
                throw new SigmaException('O campo "objective" é obrigatório.', 'mission.missing_objective');
            }

            /** @var CreateMission $createMission */
            $createMission = $this->container->get(CreateMission::class);

            $mission = $createMission->execute(
                TenantId::fromString($context->tenantId->toString()),
                WorkspaceId::fromString($context->workspaceId->toString()),
                CorrelationId::generate(),
                // Sem Intent Engine (Release 7), nenhuma Mission criada por
                // esta rota nasce de uma Intent registrada — `null` é o
                // valor honesto, não um ID inventado.
                null,
                $objective,
                $this->planFrom($body),
                new Actor(ActorType::User, $context->userId->toString()),
                $this->autonomyCeilingFrom($body),
                new \DateTimeImmutable(),
            );

            return [201, Envelope::success($this->present($mission))];
        });
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function get(?string $sessionToken, string $missionId): array
    {
        return $this->guarded(function () use ($sessionToken, $missionId) {
            $context = $this->resolveContext($sessionToken);

            /** @var GetMission $getMission */
            $getMission = $this->container->get(GetMission::class);
            $mission = $getMission->execute(MissionId::fromString($missionId));

            // Mission de outro Tenant responde 404, nunca 403: a existência
            // de uma Mission é, ela mesma, informação do Tenant dono dela
            // (MULTITENANCY.md — isolamento total de dados).
            if ($mission === null || $mission->tenantId()->toString() !== $context->tenantId->toString()) {
                return [404, Envelope::failure('mission.not_found', 'Mission não encontrada.')];
            }

            return [200, Envelope::success($this->present($mission))];
        });
    }

    /**
     * @param array<string, mixed> $body
     */
    private function planFrom(array $body): Plan
    {
        $rawCandidates = $body['plan']['subtaskCandidates'] ?? null;
        if (!is_array($rawCandidates) || $rawCandidates === []) {
            throw new SigmaException(
                'O campo "plan.subtaskCandidates" é obrigatório e precisa de pelo menos uma Subtask candidata.',
                'mission.missing_plan',
            );
        }

        $candidates = [];
        foreach ($rawCandidates as $raw) {
            if (!is_array($raw)) {
                throw new SigmaException('Cada item de "plan.subtaskCandidates" precisa ser um objeto.', 'mission.invalid_plan');
            }

            $candidates[] = new SubtaskCandidate(
                (string) ($raw['description'] ?? ''),
                isset($raw['candidateAgent']) ? (string) $raw['candidateAgent'] : null,
                isset($raw['candidateCapability']) ? (string) $raw['candidateCapability'] : null,
                (int) ($raw['requiredAutonomyLevel'] ?? 0),
            );
        }

        // `PlanSource::Manual` é o único valor possível enquanto o Planner
        // Engine (Release 6) não existir — o Plan é entrada do Mission
        // Engine, nunca produzido por ele (ADR-0092). Por isso esta rota
        // recebe o Plan pronto no corpo da requisição.
        return new Plan($candidates, PlanSource::Manual);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function autonomyCeilingFrom(array $body): int
    {
        $ceiling = (int) ($body['autonomyCeiling'] ?? 0);
        if ($ceiling < 0 || $ceiling > 3) {
            throw new SigmaException('"autonomyCeiling" precisa estar entre 0 e 3.', 'mission.invalid_autonomy_ceiling');
        }

        return $ceiling;
    }

    private function resolveContext(?string $sessionToken): \Sigma\IdentityEngine\Domain\Context
    {
        if ($sessionToken === null || $sessionToken === '') {
            throw new SigmaException('Header Authorization: Bearer <sessionToken> é obrigatório.', 'identity.session_not_found');
        }

        /** @var ResolveContext $resolveContext */
        $resolveContext = $this->container->get(ResolveContext::class);

        return $resolveContext->execute(SessionId::fromString($sessionToken), new \DateTimeImmutable());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Mission $mission): array
    {
        return [
            'missionId' => $mission->id()->toString(),
            'tenantId' => $mission->tenantId()->toString(),
            'workspaceId' => $mission->workspaceId()?->toString(),
            'correlationId' => $mission->correlationId()->toString(),
            'objective' => $mission->objective(),
            'status' => $mission->status()->value,
            'autonomyCeiling' => $mission->autonomyCeiling(),
            'actor' => ['type' => $mission->actor()->type->value, 'id' => $mission->actor()->id],
            'plan' => [
                'source' => $mission->plan()->source->value,
                'subtaskCandidates' => array_map(static fn (SubtaskCandidate $c): array => [
                    'description' => $c->description,
                    'candidateAgent' => $c->candidateAgent,
                    'candidateCapability' => $c->candidateCapability,
                    'requiredAutonomyLevel' => $c->requiredAutonomyLevel,
                ], $mission->plan()->subtaskCandidates),
            ],
            'subtasks' => array_map(static fn ($subtask): array => [
                'subtaskId' => $subtask->id()->toString(),
                'description' => $subtask->description,
                'status' => $subtask->status()->value,
            ], $mission->subtasks()),
            'pendingApprovalGateId' => $mission->pendingApprovalGate()?->id()->toString(),
            'createdAt' => $mission->createdAt()->format(\DATE_ATOM),
            'startedAt' => $mission->startedAt()?->format(\DATE_ATOM),
            'finishedAt' => $mission->finishedAt()?->format(\DATE_ATOM),
        ];
    }

    /**
     * @param callable(): array{0: int, 1: array<string, mixed>} $action
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function guarded(callable $action): array
    {
        try {
            return $action();
        } catch (SigmaException $exception) {
            return [$this->statusFor($exception->errorCode()), Envelope::failure($exception->errorCode(), $exception->getMessage())];
        } catch (\Throwable $exception) {
            // Mesma disciplina de AuthEndpoints::guarded() — uma falha de
            // infraestrutura nunca vaza mensagem interna ao cliente, e a
            // resposta continua sendo um Envelope válido.
            error_log(sprintf('[gateway] falha inesperada: %s', $exception->getMessage()));

            return [500, Envelope::failure('gateway.internal_error', 'Erro interno ao processar a requisição.')];
        }
    }

    private function statusFor(string $errorCode): int
    {
        return match ($errorCode) {
            'identity.session_expired', 'identity.invalid_credentials' => 401,
            'identity.session_not_found' => 401,
            'mission.not_found' => 404,
            'identity.workspace_not_selected' => 409,
            default => 400,
        };
    }
}
