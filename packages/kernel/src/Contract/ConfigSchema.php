<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * O que um Module precisa de configuração — declarado por ele mesmo,
 * nunca lido de variável de ambiente diretamente (ver ADR-0044,
 * Configuration Provider).
 */
final class ConfigSchema
{
    /**
     * @param string[] $required Chaves obrigatórias — o boot falha explicitamente se alguma faltar.
     * @param string[] $optional Chaves opcionais.
     * @param array<string, mixed> $defaults Valores default para chaves opcionais ausentes.
     */
    public function __construct(
        public readonly array $required = [],
        public readonly array $optional = [],
        public readonly array $defaults = [],
    ) {
    }
}
