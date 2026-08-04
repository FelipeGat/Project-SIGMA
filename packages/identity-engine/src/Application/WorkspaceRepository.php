<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Workspace;
use Sigma\IdentityEngine\Domain\WorkspaceId;

interface WorkspaceRepository
{
    public function save(Workspace $workspace): void;

    public function find(WorkspaceId $id): ?Workspace;
}
