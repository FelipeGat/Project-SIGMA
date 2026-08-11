<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

use Sigma\Core\Id;
use Sigma\Core\SigmaException;

/**
 * Base de todo identificador de domínio do Planner Engine — nunca uma
 * string primitiva (ADR-0063). Quarta cópia desta mesma implementação
 * no projeto (identity/memory/mission/planner); a consolidação em
 * `packages/core` segue recomendada e não decidida, e esta Release não
 * acopla sua Implementation a essa pendência — mesmo precedente das
 * Releases 4A e 5B.
 *
 * Não confundir com a decisão de ADR-0096: aquela é sobre `Plan`, que
 * é fronteira de bounded context; esta é encanamento sem significado
 * de domínio, e sua duplicação continua sendo dívida legítima.
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
                'planner.invalid_identifier',
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
