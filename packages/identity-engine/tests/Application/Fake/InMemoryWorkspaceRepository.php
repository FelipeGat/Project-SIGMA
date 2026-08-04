<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\WorkspaceRepository;
use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class InMemoryWorkspaceRepository implements WorkspaceRepository
{
    /** @var array<string, Workspace> */
    private array $workspaces = [];

    public function save(Workspace $workspace): void
    {
        $this->workspaces[$workspace->id()->toString()] = $workspace;
    }

    public function find(WorkspaceId $id): ?Workspace
    {
        return $this->workspaces[$id->toString()] ?? null;
    }
}
