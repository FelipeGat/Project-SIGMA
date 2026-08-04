<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\SessionId;

interface SessionRepository
{
    public function save(Session $session): void;

    public function find(SessionId $id): ?Session;

    public function delete(SessionId $id): void;
}
