<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MemoryRecordId;

/**
 * Resolve a evidência estrutural (via repositório) e repassa ao
 * Domain, que decide se promove — nunca o contrário (ver
 * MEMORY_MODEL.md#como-a-promoção-funciona e o Decision Log da 4A).
 */
final class EvaluateMemoryPromotion
{
    public function __construct(
        private readonly MemoryRecordRepository $memoryRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function toProject(MemoryRecordId $id, \DateTimeImmutable $now): ?MemoryRecord
    {
        $record = $this->find($id);

        $reinforcing = $this->memoryRecords->findReinforcingMissionIds(
            $record->subjectKey(),
            $record->workspaceId() ?? throw new SigmaException('MemoryRecord Operational sem workspaceId é um estado inválido.', 'memory.invalid_state'),
            $record->id(),
            $record->tenantId(),
        );

        $promoted = $record->evaluatePromotionToProject($reinforcing, $now);
        $this->persistAndPublish($record, $promoted);

        return $promoted;
    }

    public function toLongTerm(MemoryRecordId $id, string $generalizedSubjectKey, \DateTimeImmutable $now): ?MemoryRecord
    {
        $record = $this->find($id);

        $reinforcing = $this->memoryRecords->findReinforcingWorkspaceIds(
            $record->subjectKey(),
            $record->workspaceId() ?? throw new SigmaException('MemoryRecord Project sem workspaceId é um estado inválido.', 'memory.invalid_state'),
            $record->tenantId(),
        );

        $promoted = $record->evaluatePromotionToLongTerm($generalizedSubjectKey, $reinforcing, $now);
        $this->persistAndPublish($record, $promoted);

        return $promoted;
    }

    private function find(MemoryRecordId $id): MemoryRecord
    {
        return $this->memoryRecords->find($id)
            ?? throw new SigmaException('MemoryRecord não encontrado.', 'memory.record_not_found');
    }

    private function persistAndPublish(MemoryRecord $source, ?MemoryRecord $promoted): void
    {
        // evaluatePromotionTo*() nunca muda o registro de origem, mas
        // salvá-lo de novo é barato e mantém o repositório honesto caso
        // isso mude no futuro — sem custo real de persistência extra
        // (mesmo estado, mesma linha).
        $this->memoryRecords->save($source);

        if ($promoted === null) {
            return;
        }

        $this->memoryRecords->save($promoted);

        foreach ($promoted->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
