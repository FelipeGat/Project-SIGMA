<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IConfiguration;

/**
 * Implementação de referência de IConfiguration — resolve um ConfigSchema
 * contra uma fonte de valores (variáveis de ambiente, por padrão) e falha
 * explicitamente, no boot, se algo obrigatório faltar (ver ADR-0044).
 * Uma instância por Module — nenhum Module enxerga a configuração de outro.
 */
final class ConfigurationProvider implements IConfiguration
{
    /** @var array<string, mixed> */
    private readonly array $values;

    /**
     * @param array<string, string|null> $source Tipicamente $_ENV/getenv(); injetável para testes.
     *
     * @throws SigmaException Quando uma chave obrigatória do schema está ausente da fonte.
     */
    public function __construct(ConfigSchema $schema, array $source, private readonly string $moduleName)
    {
        $values = [];

        foreach ($schema->required as $key) {
            if (!array_key_exists($key, $source) || $source[$key] === null || $source[$key] === '') {
                throw new SigmaException(
                    sprintf('Module "%s": configuração obrigatória "%s" ausente.', $moduleName, $key),
                    'configuration.required_key_missing',
                );
            }
            $values[$key] = $source[$key];
        }

        foreach ($schema->optional as $key) {
            $values[$key] = $source[$key] ?? $schema->defaults[$key] ?? null;
        }

        $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values) && $this->values[$key] !== null;
    }

    public function require(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new SigmaException(
                sprintf('Module "%s": configuração "%s" não resolvida.', $this->moduleName, $key),
                'configuration.key_not_resolved',
            );
        }

        return $this->values[$key];
    }
}
