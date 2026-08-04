<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\ContextMemoryRepository;
use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\ContextMemoryId;
use Sigma\MemoryEngine\Domain\DistilledFact;
use Sigma\MemoryEngine\Domain\MemoryRecord;

/**
 * Fecha um ContextMemory e o destila em zero ou mais MemoryRecord — os
 * `DistilledFact` já vêm resolvidos por quem chama (o algoritmo real de
 * extração de fatos a partir de `rawContent` não existe ainda, ver
 * Proposal 4B — "Não existe ainda").
 */
final class CloseAndDistillContextMemory
{
    public function __construct(
        private readonly ContextMemoryRepository $contextMemories,
        private readonly MemoryRecordRepository $memoryRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    /**
     * @param list<DistilledFact> $facts
     * @return list<MemoryRecord>
     */
    public function execute(ContextMemoryId $id, array $facts, \DateTimeImmutable $now): array
    {
        $contextMemory = $this->contextMemories->find($id);
        if ($contextMemory === null) {
            throw new SigmaException('ContextMemory não encontrado.', 'memory.context_not_found');
        }

        $contextMemory->close($now);
        $this->contextMemories->save($contextMemory);

        $records = $contextMemory->distill($facts, MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE, $now);
        foreach ($records as $record) {
            $this->memoryRecords->save($record);
        }

        foreach ($contextMemory->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        foreach ($records as $record) {
            foreach ($record->pullDomainEvents() as $event) {
                $this->eventBus->publish($event->name(), $event->payload());
            }
        }

        return $records;
    }
}
