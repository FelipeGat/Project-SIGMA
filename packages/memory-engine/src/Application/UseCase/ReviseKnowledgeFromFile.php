<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\KnowledgeRecordRepository;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

final class ReviseKnowledgeFromFile
{
    public function __construct(
        private readonly KnowledgeRecordRepository $knowledgeRecords,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(string $sourcePath, string $title, string $content, TenantId $tenantId): KnowledgeRecord
    {
        $previous = $this->knowledgeRecords->findLatestBySourcePath($sourcePath, $tenantId);
        if ($previous === null) {
            throw new SigmaException(
                'Nenhuma versão anterior encontrada — use IndexKnowledgeFromFile para a primeira versão.',
                'memory.knowledge_source_never_indexed',
            );
        }

        $revised = KnowledgeRecord::reviseFrom($previous, $title, $content);
        $this->knowledgeRecords->save($revised);

        foreach ($revised->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $revised;
    }
}
