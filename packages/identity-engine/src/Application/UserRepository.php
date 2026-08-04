<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\User;
use Sigma\IdentityEngine\Domain\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function find(UserId $id): ?User;

    public function findByEmail(TenantId $tenantId, string $email): ?User;
}
