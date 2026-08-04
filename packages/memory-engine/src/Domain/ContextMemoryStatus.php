<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * Ver MEMORY_MODEL.md — ContextMemory. Um engajamento é Active
 * enquanto em andamento; Closed dispara a destilação em MemoryRecord.
 */
enum ContextMemoryStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
}
