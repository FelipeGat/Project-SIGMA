<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application;

/**
 * Isolado em interface própria (mesmo padrão de RedisPublisher em
 * services/event-bus) para que os casos de uso de autenticação sejam
 * testáveis sem depender do mecanismo real. Nomeado por conceito
 * ("prova de quem você é"), não por implementação — hoje só senha
 * (Argon2id, ver Infrastructure/Security/Argon2idCredentialProvider),
 * amanhã Passkey/OAuth/biometria sem precisar renomear de novo
 * (ver ADR-0072).
 */
interface CredentialProvider
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;
}
