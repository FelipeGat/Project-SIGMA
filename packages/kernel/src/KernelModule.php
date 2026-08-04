<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IHealth;
use Sigma\Kernel\Contract\ILogger;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;

/**
 * O próprio Kernel, modelado como Module (`kind: package`) — o
 * LifecycleManager não trata este Module de forma especial (ver
 * ADR-0040); ele só existe cedo porque `ILogger`/`IHealth` precisam
 * estar no Container antes de qualquer outro Module rodar `boot()`.
 * Recebe Logger e HealthManager já construídos pelo processo de
 * bootstrap (ver services/gateway) — são as mesmas instâncias
 * compartilhadas por todo o Lifecycle, não uma cópia.
 */
final class KernelModule implements IModule
{
    private const VERSION = '1.0.0';

    private bool $booted = false;

    public function __construct(
        private readonly ILogger $logger,
        private readonly IHealth $health,
    ) {
    }

    public function name(): string
    {
        return 'kernel';
    }

    public function kind(): ModuleKind
    {
        return ModuleKind::Package;
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema();
    }

    public function register(IContainer $container): void
    {
        $container->bind(ILogger::class, $this->logger);
        $container->bind(IHealth::class, $this->health);
        $container->bind(IContainer::class, $container);
    }

    public function boot(): void
    {
        $this->booted = true;
        $this->logger->info('Kernel inicializado.', ['module' => $this->name()]);
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name(),
            type: $this->kind(),
            version: self::VERSION,
            status: $this->booted ? ModuleStatus::Ready : ModuleStatus::Boot,
            capabilities: ['Logging', 'HealthReporting', 'DependencyResolution'],
            dependencies: [],
        );
    }
}
