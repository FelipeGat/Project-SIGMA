<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\EventBus\EventBusModule;
use Sigma\Kernel\Container;
use Sigma\Kernel\HealthManager;
use Sigma\Kernel\KernelModule;
use Sigma\Kernel\LifecycleManager;
use Sigma\Kernel\Logger;
use Sigma\Kernel\Manifest\SystemManifest;
use Sigma\Kernel\Manifest\SystemManifestLoader;

/**
 * Monta e inicia o SIGMA a partir do System Manifest — a
 * implementação de referência de `discover → register → boot → start
 * → ready` descrita em BOOTSTRAP.md, para esta Release (apenas os
 * Modules `kernel` e `event-bus`).
 */
final class Bootstrap
{
    private function __construct(
        public readonly HealthManager $health,
        public readonly LifecycleManager $lifecycle,
    ) {
    }

    /**
     * @param array<string, string|null> $env Tipicamente $_ENV — injetável para testes.
     */
    public static function fromManifestFile(string $manifestPath, array $env): self
    {
        $manifest = (new SystemManifestLoader())->loadFromFile($manifestPath);

        return self::boot($manifest, $env);
    }

    /**
     * @param array<string, string|null> $env
     */
    public static function boot(SystemManifest $manifest, array $env): self
    {
        $logger = new Logger();
        $health = new HealthManager();
        $container = new Container();
        $lifecycle = new LifecycleManager($container, $health, $logger);

        $lifecycle->registerModule(new KernelModule($logger, $health));
        $lifecycle->registerModule(new EventBusModule($env));

        $lifecycle->boot($manifest);

        return new self($health, $lifecycle);
    }
}
