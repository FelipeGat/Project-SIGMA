<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

/**
 * Isolado em interface própria (mesmo padrão de RedisPublisher em
 * services/event-bus) para que os casos de uso de autenticação sejam
 * testáveis sem depender do algoritmo real. A implementação de
 * produção usa Argon2id — ver Infrastructure/Security/Argon2idPasswordHasher.
 */
interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;
}
