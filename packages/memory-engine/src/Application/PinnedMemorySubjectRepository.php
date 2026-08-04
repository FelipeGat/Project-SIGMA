<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

interface PinnedMemorySubjectRepository
{
    public function save(PinnedMemorySubject $pin): void;

    public function find(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): ?PinnedMemorySubject;

    public function delete(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): void;
}
