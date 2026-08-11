<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * O que o Planner recebe. **Não** é o aggregate do Intent Engine
 * (Release 7) — é a forma de dado que o Planner exige na entrada,
 * própria dele, pelo mesmo princípio de ADR-0092/ADR-0096.
 *
 * `correlationId` atravessa o Planner sem ser alterado e chega ao
 * Mission Engine, que o exige em `Mission::create()`. Sem ele, o
 * consumidor teria que gerar um novo e a rastreabilidade fim-a-fim se
 * perderia exatamente na fronteira entre decidir e executar.
 */
final class Intent
{
    /** @param array<string, scalar> $parameters */
    public function __construct(
        public readonly IntentId $id,
        public readonly CorrelationId $correlationId,
        public readonly TenantId $tenantId,
        public readonly ?WorkspaceId $workspaceId,
        public readonly string $objective,
        public readonly IntentKind $kind,
        public readonly array $parameters,
        public readonly Actor $actor,
        public readonly int $autonomyCeiling,
    ) {
        if (trim($objective) === '') {
            throw new SigmaException('Intent.objective não pode ser vazio.', 'planner.invalid_intent');
        }

        if ($autonomyCeiling < 0 || $autonomyCeiling > 3) {
            throw new SigmaException('Intent.autonomyCeiling precisa estar entre 0 e 3.', 'planner.invalid_autonomy_ceiling');
        }
    }

    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]) && trim((string) $this->parameters[$name]) !== '';
    }
}
