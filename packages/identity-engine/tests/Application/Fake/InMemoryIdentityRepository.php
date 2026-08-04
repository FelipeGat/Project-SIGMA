<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\UserId;

final class InMemoryIdentityRepository implements IdentityRepository
{
    /** @var array<string, Identity> */
    private array $identities = [];

    public function save(Identity $identity): void
    {
        $this->identities[$identity->id()->toString()] = $identity;
    }

    public function find(IdentityId $id): ?Identity
    {
        return $this->identities[$id->toString()] ?? null;
    }

    public function findByUserId(UserId $userId): ?Identity
    {
        foreach ($this->identities as $identity) {
            if ($identity->user()->id()->equals($userId)) {
                return $identity;
            }
        }

        return null;
    }
}
