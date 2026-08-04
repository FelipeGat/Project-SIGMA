<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IContainer;

/**
 * Implementação de referência de IContainer — resolução por contrato,
 * bindings lazy (factory só é chamada na primeira resolução).
 */
final class Container implements IContainer
{
    /** @var array<string, object> Closure (factory, lazy) ou instância já pronta. */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    public function bind(string $id, object $concrete): void
    {
        $this->bindings[$id] = $concrete;
        unset($this->resolved[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            throw new SigmaException(
                sprintf('Nenhum binding registrado para "%s".', $id),
                'container.binding_not_found',
            );
        }

        $concrete = $this->bindings[$id];
        $instance = $concrete instanceof \Closure ? $concrete($this) : $concrete;

        return $this->resolved[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }
}
