<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\Core\Envelope;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\Kernel\Contract\IContainer;
use Sigma\PlannerEngine\Application\UseCase\PlanFromIntent;
use Sigma\PlannerEngine\Domain\Actor;
use Sigma\PlannerEngine\Domain\ActorType;
use Sigma\PlannerEngine\Domain\CorrelationId;
use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\IntentId;
use Sigma\PlannerEngine\Domain\IntentKind;
use Sigma\PlannerEngine\Domain\Plan;
use Sigma\PlannerEngine\Domain\TenantId;
use Sigma\PlannerEngine\Domain\WorkspaceId;

/**
 * `POST /intents` — a porta de entrada do planejamento (Release 6B).
 *
 * O usuário descreve **o quê** (`objective`, `kind`, `parameters`); o
 * SIGMA decide **o como**. É o oposto de `POST /missions` (Release
 * 5.5), onde quem chama escreve os passos — as duas rotas coexistem, e
 * `Plan.source` (`planner` vs. `manual`) é o que as distingue depois.
 *
 * Responde **202**, não 201: a Mission ainda não existe neste
 * instante. O Planner publica `mission.planned` e esquece (ADR-0089);
 * quem cria a Mission é `services/mission-worker`, de forma assíncrona.
 * Responder 201 exigiria o gateway esperar o worker, reintroduzindo o
 * acoplamento síncrono entre Engines que ADR-0008 proíbe.
 *
 * `tenantId`/`workspaceId`/`actor`/`autonomyCeiling` vêm da Session,
 * nunca do corpo — mesma disciplina de {@see MissionEndpoints}.
 *
 * @see public/index.php
 */
final class IntentEndpoints
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
                throw new SigmaException('O campo "objective" é obrigatório.', 'intent.missing_objective');
            }

            $kind = IntentKind::tryFrom((string) ($body['kind'] ?? ''));
            if ($kind === null) {
                throw new SigmaException(
                    sprintf(
                        '"kind" inválido. Valores aceitos: %s.',
                        implode(', ', array_map(static fn (IntentKind $k): string => $k->value, IntentKind::cases())),
                    ),
                    'intent.invalid_kind',
                );
            }

            $intent = new Intent(
                IntentId::generate(),
                CorrelationId::generate(),
                TenantId::fromString($context->tenantId->toString()),
                WorkspaceId::fromString($context->workspaceId->toString()),
                $objective,
                $kind,
                $this->parametersFrom($body),
                new Actor(ActorType::User, $context->userId->toString()),
                // O teto de autonomia de quem pediu. Nesta Release vem
                // fixo em 0 (Consultivo): o Identity Engine ainda não
                // expõe o nível configurado por User/Role, e assumir um
                // valor mais alto faria Subtasks sensíveis executarem
                // sem o gate que o Playbook exige. Ver Decision Log.
                0,
            );

            /** @var PlanFromIntent $planFromIntent */
            $planFromIntent = $this->container->get(PlanFromIntent::class);
            $outcome = $planFromIntent->execute($intent);

            if ($outcome instanceof Plan) {
                return [202, Envelope::success([
                    'intentId' => $intent->id->toString(),
                    'correlationId' => $intent->correlationId->toString(),
                    'kind' => $kind->value,
                    'status' => 'planned',
                    'subtaskCandidates' => count($outcome->subtaskCandidates),
                    // A Mission ainda não existe: quem a cria é o
                    // mission-worker, ao consumir mission.planned.
                    'note' => 'Plan publicado. A Mission é criada de forma assíncrona — acompanhe por GET /missions/{id} quando o identificador estiver disponível.',
                ])];
            }

            // Planejamento impossível não é erro do cliente nem do
            // servidor: o pedido foi entendido e aceito, e o sistema
            // reconhece que não sabe executá-lo (ADR-0097). 422 é o
            // status que expressa isso sem fingir que a requisição
            // estava malformada.
            return [422, Envelope::failure(
                sprintf('planning.%s', $outcome->reason->value),
                $outcome->detail,
            )];
        });
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, scalar>
     */
    private function parametersFrom(array $body): array
    {
        $raw = $body['parameters'] ?? [];
        if (!is_array($raw)) {
            throw new SigmaException('"parameters" precisa ser um objeto.', 'intent.invalid_parameters');
        }

        $parameters = [];
        foreach ($raw as $name => $value) {
            if (!is_scalar($value)) {
                throw new SigmaException(
                    sprintf('"parameters.%s" precisa ser um valor simples.', (string) $name),
                    'intent.invalid_parameters',
                );
            }

            $parameters[(string) $name] = $value;
        }

        return $parameters;
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
            error_log(sprintf('[gateway] falha inesperada: %s', $exception->getMessage()));

            return [500, Envelope::failure('gateway.internal_error', 'Erro interno ao processar a requisição.')];
        }
    }

    private function statusFor(string $errorCode): int
    {
        return match ($errorCode) {
            'identity.session_expired', 'identity.invalid_credentials', 'identity.session_not_found' => 401,
            'identity.workspace_not_selected' => 409,
            default => 400,
        };
    }
}
