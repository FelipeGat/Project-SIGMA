<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Resolve dependências por contrato (interface), nunca por
 * implementação concreta — ver ADR-0052. Um Module nunca importa a
 * classe concreta por trás de um binding, apenas a interface.
 */
interface IContainer
{
    /**
     * Registra um binding. `$concrete` é uma instância já pronta ou uma
     * factory `Closure(IContainer): mixed` (lazy — resolvida só na
     * primeira chamada a `get()`, detectada via `instanceof \Closure`).
     */
    public function bind(string $id, object $concrete): void;

    public function get(string $id): mixed;

    public function has(string $id): bool;
}
