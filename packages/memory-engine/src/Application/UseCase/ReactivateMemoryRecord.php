<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\MemoryRecordRepository;
use Sigma\MemoryEngine\Domain\MemoryRecordId;

final class ReactivateMemoryRecord
{
    public function __construct(
        private readonly MemoryRecordRepository $memoryRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(MemoryRecordId $id): void
    {
        $record = $this->memoryRecords->find($id)
            ?? throw new SigmaException('MemoryRecord não encontrado.', 'memory.record_not_found');

        $record->reactivate();
        $this->memoryRecords->save($record);

        foreach ($record->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }
    }
}
