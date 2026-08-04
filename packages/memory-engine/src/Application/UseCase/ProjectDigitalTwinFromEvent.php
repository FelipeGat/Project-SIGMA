<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\DigitalTwinRepository;
use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

/**
 * O handler que `MemoryEngineModule` regista em `IEventBus::subscribe()`
 * para `identity.created`/`workspace.selected` — o único caminho de
 * criação/atualização de `DigitalTwin` (ADR-0085: sempre Evento →
 * Projection → Twin, nunca leitura direta).
 *
 * `externalRef` usa `identityId`, não `userId` como MEMORY_MODEL.md
 * (revisão 2) descrevia literalmente — refinamento encontrado ao
 * ligar os dois eventos de fato: `workspace.selected` carrega
 * `identityId`, nunca `userId`. Os dois são 1:1 e igualmente internos
 * ao SIGMA (nenhum é um id de sistema externo) — a troca preserva o
 * espírito da decisão original. Ver Decision Log da Release 4B.
 */
final class ProjectDigitalTwinFromEvent
{
    public function __construct(
        private readonly DigitalTwinRepository $digitalTwins,
        private readonly IEventBus $eventBus,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function handle(string $eventName, array $payload, \DateTimeImmutable $now): void
    {
        match ($eventName) {
            'identity.created' => $this->handleIdentityCreated($payload, $now),
            'workspace.selected' => $this->handleWorkspaceSelected($payload, $now),
            default => null, // evento não relevante para este handler — ignorado, nunca um erro.
        };
    }

    /** @param array<string, mixed> $payload */
    private function handleIdentityCreated(array $payload, \DateTimeImmutable $now): void
    {
        $identityId = (string) $payload['identityId'];
        $tenantId = TenantId::fromString((string) $payload['tenantId']);
        $state = ['identityId' => $identityId, 'userId' => (string) $payload['userId']];

        $existing = $this->digitalTwins->findBySubjectTypeAndExternalRef(TwinSubjectType::User, $identityId);
        if ($existing !== null) {
            // Redelivery do mesmo evento — idempotente, só reforça o estado já conhecido.
            $existing->applyProjection($state, $now);
            $this->save($existing);

            return;
        }

        $twin = DigitalTwin::project(TwinSubjectType::User, $identityId, $state, $tenantId, $now);
        $this->save($twin);
    }

    /** @param array<string, mixed> $payload */
    private function handleWorkspaceSelected(array $payload, \DateTimeImmutable $now): void
    {
        $identityId = (string) $payload['identityId'];

        $twin = $this->digitalTwins->findBySubjectTypeAndExternalRef(TwinSubjectType::User, $identityId);
        if ($twin === null) {
            // Entrega fora de ordem (workspace.selected antes de identity.created
            // ter sido processado) — sem retry/fila nesta Release (Proposal 4B,
            // "Não existe ainda"), ignorado silenciosamente em vez de derrubar o
            // worker. Sinalizado como pendência no Validation Report.
            return;
        }

        $twin->applyProjection([...$twin->state(), 'workspaceId' => (string) $payload['workspaceId']], $now);
        $this->save($twin);
    }

    private function save(DigitalTwin $twin): void
    {
        $this->digitalTwins->save($twin);

        foreach ($twin->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
