<?php

declare(strict_types=1);

namespace Sigma\Kernel\Manifest;

/**
 * O System Manifest já lido e validado estruturalmente — ver
 * SYSTEM_MANIFEST.md. `providers` e `workspace` são passados adiante
 * tal como declarados; o Bootstrap (Release 2) não os interpreta —
 * isso é trabalho do Agent Engine e do Identity Engine, respectivamente.
 */
final class SystemManifest
{
    /**
     * @param ModuleReference[] $modules
     * @param string[] $providers
     * @param string[] $workspace
     */
    public function __construct(
        public readonly string $project,
        public readonly string $version,
        public readonly array $modules,
        public readonly array $providers = [],
        public readonly array $workspace = [],
    ) {
    }

    public function requiredModuleNames(): array
    {
        return array_values(array_map(
            static fn (ModuleReference $ref): string => $ref->name,
            array_filter($this->modules, static fn (ModuleReference $ref): bool => !$ref->optional),
        ));
    }

    public function optionalModuleNames(): array
    {
        return array_values(array_map(
            static fn (ModuleReference $ref): string => $ref->name,
            array_filter($this->modules, static fn (ModuleReference $ref): bool => $ref->optional),
        ));
    }

    public function has(string $moduleName): bool
    {
        foreach ($this->modules as $module) {
            if ($module->name === $moduleName) {
                return true;
            }
        }

        return false;
    }
}
