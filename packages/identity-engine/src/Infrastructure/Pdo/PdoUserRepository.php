<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\UserRepository;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\User;
use Sigma\IdentityEngine\Domain\UserId;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(User $user): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (id, tenant_id, name, email, created_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email)',
        );
        $statement->execute([
            $user->id()->toString(),
            $user->tenantId()->toString(),
            $user->name(),
            $user->email(),
        ]);
    }

    public function find(UserId $id): ?User
    {
        $statement = $this->pdo->prepare('SELECT id, tenant_id, name, email FROM users WHERE id = ?');
        $statement->execute([$id->toString()]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    public function findByEmail(TenantId $tenantId, string $email): ?User
    {
        $statement = $this->pdo->prepare('SELECT id, tenant_id, name, email FROM users WHERE tenant_id = ? AND email = ?');
        $statement->execute([$tenantId->toString(), $email]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate($row): ?User
    {
        if ($row === false) {
            return null;
        }

        return new User(UserId::fromString($row['id']), TenantId::fromString($row['tenant_id']), $row['name'], $row['email']);
    }
}
