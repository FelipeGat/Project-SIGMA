<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\ContextMemoryId;

interface ContextMemoryRepository
{
    public function save(ContextMemory $contextMemory): void;

    public function find(ContextMemoryId $id): ?ContextMemory;
}
