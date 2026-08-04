<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Application\Fake;

use Sigma\IdentityEngine\Application\CredentialRepository;
use Sigma\IdentityEngine\Domain\UserId;

final class InMemoryCredentialRepository implements CredentialRepository
{
    /** @var array<string, string> */
    private array $hashes = [];

    public function setPasswordHash(UserId $userId, string $hash): void
    {
        $this->hashes[$userId->toString()] = $hash;
    }

    public function passwordHash(UserId $userId): ?string
    {
        return $this->hashes[$userId->toString()] ?? null;
    }
}
