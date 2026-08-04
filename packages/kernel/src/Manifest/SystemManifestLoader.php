<?php

declare(strict_types=1);

namespace Sigma\Kernel\Manifest;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\ModuleKind;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Etapa `discover` do Lifecycle (ver BOOTSTRAP.md): lê o System Manifest
 * e o valida estruturalmente — antes de qualquer Module ser registrado.
 * Um Manifest malformado ou com uma referência inválida falha aqui,
 * explicitamente, nunca em runtime mais adiante.
 */
final class SystemManifestLoader
{
    public function loadFromFile(string $path): SystemManifest
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new SigmaException(
                sprintf('System Manifest não encontrado ou ilegível: "%s".', $path),
                'manifest.file_not_found',
            );
        }

        return $this->loadFromString(file_get_contents($path));
    }

    public function loadFromString(string $yaml): SystemManifest
    {
        try {
            $data = Yaml::parse($yaml);
        } catch (ParseException $exception) {
            throw new SigmaException(
                sprintf('System Manifest inválido: %s', $exception->getMessage()),
                'manifest.invalid_yaml',
                $exception,
            );
        }

        if (!is_array($data)) {
            throw new SigmaException('System Manifest vazio ou não é um mapeamento YAML válido.', 'manifest.invalid_structure');
        }

        foreach (['project', 'version'] as $required) {
            if (!isset($data[$required]) || !is_string($data[$required]) || $data[$required] === '') {
                throw new SigmaException(
                    sprintf('System Manifest inválido: campo obrigatório "%s" ausente.', $required),
                    'manifest.missing_field',
                );
            }
        }

        $modules = [];
        foreach ((array) ($data['modules'] ?? []) as $index => $entry) {
            $modules[] = $this->parseModuleReference($entry, $index);
        }

        return new SystemManifest(
            project: $data['project'],
            version: (string) $data['version'],
            modules: $modules,
            providers: array_map('strval', (array) ($data['providers'] ?? [])),
            workspace: array_map('strval', (array) ($data['workspace'] ?? [])),
        );
    }

    /**
     * @param mixed $entry
     */
    private function parseModuleReference($entry, int $index): ModuleReference
    {
        if (is_string($entry)) {
            $entry = ['name' => $entry];
        }

        if (!is_array($entry) || !isset($entry['name']) || !is_string($entry['name']) || $entry['name'] === '') {
            throw new SigmaException(
                sprintf('System Manifest inválido: entrada de módulo #%d sem "name" válido.', $index),
                'manifest.invalid_module_entry',
            );
        }

        $kindValue = $entry['kind'] ?? 'package';
        $kind = ModuleKind::tryFrom((string) $kindValue);

        if ($kind === null) {
            throw new SigmaException(
                sprintf('System Manifest inválido: módulo "%s" tem kind "%s" desconhecido.', $entry['name'], $kindValue),
                'manifest.invalid_module_kind',
            );
        }

        return new ModuleReference(
            name: $entry['name'],
            kind: $kind,
            optional: (bool) ($entry['optional'] ?? false),
            minVersion: isset($entry['minVersion']) ? (string) $entry['minVersion'] : null,
        );
    }
}
