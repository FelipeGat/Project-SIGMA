<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

use Sigma\Core\SigmaException;

/**
 * A configuração já resolvida de um Module — nunca lida de variável
 * de ambiente diretamente por ele (ver ADR-0044, Configuration Provider).
 */
interface IConfiguration
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @throws SigmaException Quando a chave é obrigatória e está ausente.
     */
    public function require(string $key): mixed;
}
