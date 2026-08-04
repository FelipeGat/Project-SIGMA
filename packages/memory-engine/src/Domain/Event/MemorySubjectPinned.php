<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

use Sigma\MemoryEngine\Domain\WorkspaceId;

/**
 * Catalogado (DOMAIN_EVENTS.md) mas, deliberadamente, sem um método de
 * Aggregate que o produza nesta sub-Release: fixar um `subjectKey`
 * contra promoção automática é uma ação de governança sem estado de
 * domínio associado ao Operational/Project/LongTerm de MemoryRecord —
 * é a Application (4B), ao verificar a Permission
 * `memory.block_promotion`, quem publica este evento diretamente. Ver
 * docs/releases/0004a-memory-domain-decision-log.md.
 */
final class MemorySubjectPinned implements DomainEvent
{
    public function __construct(
        public readonly string $subjectKey,
        public readonly WorkspaceId $workspaceId,
        public readonly string $actor,
    ) {
    }

    public function name(): string
    {
        return 'memory.subject_pinned';
    }

    public function payload(): array
    {
        return [
            'subjectKey' => $this->subjectKey,
            'workspaceId' => $this->workspaceId->toString(),
            'actor' => $this->actor,
        ];
    }
}
