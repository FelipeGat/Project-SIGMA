<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application\Fake;

use Sigma\MemoryEngine\Application\PinnedMemorySubject;
use Sigma\MemoryEngine\Application\PinnedMemorySubjectRepository;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class InMemoryPinnedMemorySubjectRepository implements PinnedMemorySubjectRepository
{
    /** @var array<string, PinnedMemorySubject> */
    private array $pins = [];

    public function save(PinnedMemorySubject $pin): void
    {
        $this->pins[$this->key($pin->subjectKey, $pin->workspaceId, $pin->tenantId)] = $pin;
    }

    public function find(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): ?PinnedMemorySubject
    {
        return $this->pins[$this->key($subjectKey, $workspaceId, $tenantId)] ?? null;
    }

    public function delete(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): void
    {
        unset($this->pins[$this->key($subjectKey, $workspaceId, $tenantId)]);
    }

    private function key(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId): string
    {
        return $tenantId->toString() . ':' . $workspaceId->toString() . ':' . $subjectKey;
    }
}
