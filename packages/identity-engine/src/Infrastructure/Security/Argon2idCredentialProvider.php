<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Security;

use Sigma\IdentityEngine\Application\CredentialProvider;

/**
 * Só primitivas padrão do PHP — nunca criptografia própria (ver Riscos
 * da Proposal da Release 3B). Renomeado de Argon2idPasswordHasher na
 * Release 3.5 — mesmo algoritmo, mesmo comportamento (ver ADR-0072).
 */
final class Argon2idCredentialProvider implements CredentialProvider
{
    public function hash(string $plain): string
    {
        return password_hash($plain, \PASSWORD_ARGON2ID);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
