<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests\Fixtures;

use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;

/**
 * Duplo de teste de um Module — usado para validar o Lifecycle Manager
 * sem depender de nenhum Module real (nenhum existe ainda além do
 * próprio Kernel/Bootstrap).
 */
final class TestModule implements IModule
{
    public bool $registered = false;

    public bool $booted = false;

    public function __construct(
        private readonly string $name,
        private readonly array $dependsOn = [],
        private readonly ModuleKind $kind = ModuleKind::Package,
        private readonly string $version = '1.0.0',
        private readonly ?\Closure $onBoot = null,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function kind(): ModuleKind
    {
        return $this->kind;
    }

    public function dependsOn(): array
    {
        return $this->dependsOn;
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema();
    }

    public function register(IContainer $container): void
    {
        $this->registered = true;
    }

    public function boot(): void
    {
        $this->booted = true;

        if ($this->onBoot !== null) {
            ($this->onBoot)();
        }
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name,
            type: $this->kind,
            version: $this->version,
            status: $this->booted ? ModuleStatus::Ready : ModuleStatus::Boot,
        );
    }
}
