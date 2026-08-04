<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\Id;
use Sigma\Core\SigmaException;

/**
 * Base de todo identificador de domínio do Mission Engine — nunca uma
 * string primitiva (ADR-0063). Mesma implementação de
 * `Sigma\IdentityEngine\Domain\Identifier`/`Sigma\MemoryEngine\Domain\Identifier`
 * — a consolidação em `packages/core` segue recomendada e não
 * decidida (ver Decision Log da Release 4A/4B); Mission Engine segue
 * o mesmo precedente de manter sua própria cópia em vez de acoplar
 * esta Implementation a essa decisão pendente.
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
                'mission.invalid_identifier',
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
