<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * Os três níveis de Memory (ADR-0022) — Operational é escopado a uma
 * Mission, Project a um Workspace, LongTerm é cross-Workspace. Ver
 * MEMORY_MODEL.md — MemoryRecord.
 */
enum MemoryLevel: string
{
    case Operational = 'operational';
    case Project = 'project';
    case LongTerm = 'long_term';
}
