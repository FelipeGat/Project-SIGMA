<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

/**
 * "Este subjectKey, neste Workspace, está fixado contra promoção
 * automática, por este actor" — MEMORY_PROMOTION_RULES.md ("Quem pode
 * impedir"). Vive em `Application/`, não em `Domain/`: não tem
 * invariante de negócio que só um Aggregate possa proteger, é puramente
 * "existe ou não existe esta marca" (ver Decision Log da Release 4A e
 * a Proposal 4B — Arquitetura).
 */
final class PinnedMemorySubject
{
    public function __construct(
        public readonly string $subjectKey,
        public readonly WorkspaceId $workspaceId,
        public readonly TenantId $tenantId,
        public readonly string $actor,
        public readonly \DateTimeImmutable $pinnedAt,
    ) {
    }
}
