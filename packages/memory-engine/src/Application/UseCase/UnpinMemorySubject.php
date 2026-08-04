<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\MemoryEngine\Application\PinnedMemorySubjectRepository;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class UnpinMemorySubject
{
    public function __construct(private readonly PinnedMemorySubjectRepository $pinnedSubjects)
    {
    }

    public function execute(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): void
    {
        $this->pinnedSubjects->delete($subjectKey, $workspaceId, $tenantId);
    }
}
