<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Application\UserRepository;
use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\UserId;

final class PdoIdentityRepository implements IdentityRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly UserRepository $users,
        private readonly TenantRepository $tenants,
    ) {
    }

    public function save(Identity $identity): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO identities (id, user_id, tenant_id, active, created_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE active = VALUES(active)',
        );
        $statement->execute([
            $identity->id()->toString(),
            $identity->user()->id()->toString(),
            $identity->tenant()->id()->toString(),
            $identity->isActive() ? 1 : 0,
        ]);
    }

    public function find(IdentityId $id): ?Identity
    {
        $statement = $this->pdo->prepare('SELECT id, user_id, tenant_id, active FROM identities WHERE id = ?');
        $statement->execute([$id->toString()]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    public function findByUserId(UserId $userId): ?Identity
    {
        $statement = $this->pdo->prepare('SELECT id, user_id, tenant_id, active FROM identities WHERE user_id = ?');
        $statement->execute([$userId->toString()]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate($row): ?Identity
    {
        if ($row === false) {
            return null;
        }

        $user = $this->users->find(UserId::fromString($row['user_id']));
        $tenant = $this->tenants->find(TenantId::fromString($row['tenant_id']));
        if ($user === null || $tenant === null) {
            return null;
        }

        return Identity::reconstitute(IdentityId::fromString($row['id']), $user, $tenant, (bool) $row['active']);
    }
}
