<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class PdoSessionRepository implements SessionRepository
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Session $session): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO sessions (id, identity_id, issued_at, expires_at, workspace_id) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE workspace_id = VALUES(workspace_id)',
        );
        $statement->execute([
            $session->id()->toString(),
            $session->identityId()->toString(),
            $session->issuedAt()->format(self::DATETIME_FORMAT),
            $session->expiresAt()->format(self::DATETIME_FORMAT),
            $session->workspaceId()?->toString(),
        ]);
    }

    public function find(SessionId $id): ?Session
    {
        $statement = $this->pdo->prepare('SELECT id, identity_id, issued_at, expires_at, workspace_id FROM sessions WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Session::reconstitute(
            SessionId::fromString($row['id']),
            IdentityId::fromString($row['identity_id']),
            new \DateTimeImmutable($row['issued_at']),
            new \DateTimeImmutable($row['expires_at']),
            $row['workspace_id'] === null ? null : WorkspaceId::fromString($row['workspace_id']),
        );
    }

    public function delete(SessionId $id): void
    {
        $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id->toString()]);
    }
}
