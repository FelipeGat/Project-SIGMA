<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\MemoryRecordId;

/**
 * Ação humana explícita (Permission `memory.block_promotion`, ver
 * ADR-0088/MEMORY_PROMOTION_RULES.md) — a checagem de Permission em si
 * não é feita aqui: `Application/` não conhece Identity/Context nesta
 * Release (ver Proposal 4B — "Não existe ainda"), fica para quando
 * houver um consumidor real autenticado.
 */
final class RetractMemoryRecord
{
    public function __construct(
        private readonly MemoryRecordRepository $memoryRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(MemoryRecordId $id, string $actor): void
    {
        $record = $this->memoryRecords->find($id)
            ?? throw new SigmaException('MemoryRecord não encontrado.', 'memory.record_not_found');

        $record->retract($actor);
        $this->memoryRecords->save($record);

        foreach ($record->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
