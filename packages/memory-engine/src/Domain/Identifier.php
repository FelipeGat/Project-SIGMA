<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\Id;
use Sigma\Core\SigmaException;

/**
 * Base de todo identificador de domínio do Memory Engine — nunca uma
 * string primitiva (ADR-0063). Mesma implementação de
 * `Sigma\IdentityEngine\Domain\Identifier`, deliberadamente não
 * compartilhada via `packages/core` nesta sub-Release: mover a base
 * para `packages/core` foi sinalizado como pergunta em aberto na
 * Proposal 4A (Arquitetura) e não foi decidido explicitamente na
 * aprovação — manter os dois Engines com sua própria cópia evita
 * acoplar a Implementation da Release 4A a uma mudança retroativa no
 * Identity Engine já validado, sem fechar a porta a uma consolidação
 * futura (ver docs/releases/0004a-memory-domain-decision-log.md).
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
                'memory.invalid_identifier',
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
