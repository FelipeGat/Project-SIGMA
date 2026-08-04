<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\CredentialRepository;
use Sigma\IdentityEngine\Domain\UserId;

final class PdoCredentialRepository implements CredentialRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function setPasswordHash(UserId $userId, string $hash): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $statement->execute([$hash, $userId->toString()]);
    }

    public function passwordHash(UserId $userId): ?string
    {
        $statement = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $statement->execute([$userId->toString()]);
        $hash = $statement->fetchColumn();

        return $hash === false || $hash === null ? null : $hash;
    }
}
