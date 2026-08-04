<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\SessionId;

final class InMemorySessionRepository implements SessionRepository
{
    /** @var array<string, Session> */
    private array $sessions = [];

    public function save(Session $session): void
    {
        $this->sessions[$session->id()->toString()] = $session;
    }

    public function find(SessionId $id): ?Session
    {
        return $this->sessions[$id->toString()] ?? null;
    }

    public function delete(SessionId $id): void
    {
        unset($this->sessions[$id->toString()]);
    }
}
