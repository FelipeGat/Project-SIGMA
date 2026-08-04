<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\UserId;

interface IdentityRepository
{
    public function save(Identity $identity): void;

    public function find(IdentityId $id): ?Identity;

    public function findByUserId(UserId $userId): ?Identity;
}
