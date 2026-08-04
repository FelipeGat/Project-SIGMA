<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

use Sigma\IdentityEngine\Domain\UserId;

/**
 * Separado de UserRepository deliberadamente — o hash de senha é uma
 * preocupação de segurança/infraestrutura, não um campo do agregado
 * de domínio `User` (que descreve identidade de negócio, não
 * credencial). Ver Decision Log da Release 3B.
 */
interface CredentialRepository
{
    public function setPasswordHash(UserId $userId, string $hash): void;

    public function passwordHash(UserId $userId): ?string;
}
