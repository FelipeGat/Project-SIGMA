<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Application\ContextMemoryRepository;
use Sigma\MemoryEngine\Domain\ContextMemoryId;

final class AppendContextMemoryContent
{
    public function __construct(private readonly ContextMemoryRepository $contextMemories)
    {
    }

    public function execute(ContextMemoryId $id, string $chunk): void
    {
        $contextMemory = $this->contextMemories->find($id);
        if ($contextMemory === null) {
            throw new SigmaException('ContextMemory não encontrado.', 'memory.context_not_found');
        }

        $contextMemory->appendContent($chunk);
        $this->contextMemories->save($contextMemory);
    }
}
