<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\KnowledgeRecordRepository;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

/**
 * Indexa um arquivo de `/knowledge` pela primeira vez — se `sourcePath`
 * já tiver uma versão, use `ReviseKnowledgeFromFile` em vez deste
 * (ADR-0086: nunca editar em lugar).
 */
final class IndexKnowledgeFromFile
{
    public function __construct(
        private readonly KnowledgeRecordRepository $knowledgeRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(string $area, string $sourcePath, string $title, string $content, TenantId $tenantId): KnowledgeRecord
    {
        $record = KnowledgeRecord::index($area, $sourcePath, $title, $content, $tenantId);
        $this->knowledgeRecords->save($record);

        foreach ($record->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $record;
    }
}
