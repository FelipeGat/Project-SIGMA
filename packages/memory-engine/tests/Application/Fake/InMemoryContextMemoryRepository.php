<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application\Fake;

use Sigma\MemoryEngine\Application\ContextMemoryRepository;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\ContextMemoryId;

final class InMemoryContextMemoryRepository implements ContextMemoryRepository
{
    /** @var array<string, ContextMemory> */
    private array $records = [];

    public function save(ContextMemory $contextMemory): void
    {
        $this->records[$contextMemory->id()->toString()] = $contextMemory;
    }

    public function find(ContextMemoryId $id): ?ContextMemory
    {
        return $this->records[$id->toString()] ?? null;
    }
}
