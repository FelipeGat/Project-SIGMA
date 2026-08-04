<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\UserRepository;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\User;
use Sigma\IdentityEngine\Domain\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id()->toString()] = $user;
    }

    public function find(UserId $id): ?User
    {
        return $this->users[$id->toString()] ?? null;
    }

    public function findByEmail(TenantId $tenantId, string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->tenantId()->equals($tenantId) && $user->email() === $email) {
                return $user;
            }
        }

        return null;
    }
}
