<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\Core\Id;
use Sigma\Core\SigmaException;

/**
 * Base de todo identificador de domínio do Identity Engine — nunca uma
 * string primitiva (ADR-0063). Cada identificador concreto (TenantId,
 * UserId, etc.) é uma classe `final` distinta que estende esta, o que
 * torna `assignRole(UserId $user, RoleId $role)` impossível de chamar
 * com os argumentos trocados — o compilador rejeita antes de rodar.
 */
abstract class Identifier
{
    final private function __construct(private readonly string $value)
    {
    }

    /** @return static */
    final public static function generate(): self
    {
        return new static(Id::generate());
    }

    /** @return static */
    final public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw new SigmaException(
                sprintf('%s não pode ser vazio.', static::class),
                'identity.invalid_identifier',
            );
        }

        return new static($value);
    }

    final public function toString(): string
    {
        return $this->value;
    }

    final public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
