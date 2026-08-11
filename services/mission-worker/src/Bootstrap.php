<?php

declare(strict_types=1);

namespace Sigma\MissionWorker;

use Sigma\EventBus\EventBusModule;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\HealthManager;
use Sigma\Kernel\KernelModule;
use Sigma\Kernel\LifecycleManager;
use Sigma\Kernel\Logger;
use Sigma\Kernel\Manifest\SystemManifest;
use Sigma\Kernel\Manifest\SystemManifestLoader;
use Sigma\MissionEngine\Interfaces\MissionEngineModule;

/**
 * Monta e inicia o Mission Engine a partir do System Manifest — mesmo
 * padrão de services/memory-worker/src/Bootstrap.php (Release 4B),
 * sem nenhuma camada HTTP: este processo nunca escuta uma porta.
 *
 * Diferente do memory-worker, o Module que ele registra não assina
 * nada por si só — `MissionEngineModule::register()` apenas binda
 * casos de uso. Quem liga `mission.planned` a
 * `CreateMissionFromPlannedEvent` é `bin/worker.php`, explicitamente.
 */
final class Bootstrap
{
    private function __construct(
        public readonly HealthManager $health,
        public readonly LifecycleManager $lifecycle,
        public readonly IContainer $container,
    ) {
    }

    /** @param array<string, string|null> $env Tipicamente getenv() — injetável para testes. */
    public static function fromManifestFile(string $manifestPath, array $env): self
    {
        $manifest = (new SystemManifestLoader())->loadFromFile($manifestPath);

        return self::boot($manifest, $env);
    }

    /** @param array<string, string|null> $env */
    public static function boot(SystemManifest $manifest, array $env): self
    {
        $logger = new Logger();
        $health = new HealthManager();
        $container = new Container();
        $lifecycle = new LifecycleManager($container, $health, $logger);

        $lifecycle->registerModule(new KernelModule($logger, $health));
        $lifecycle->registerModule(new EventBusModule($env));
        $lifecycle->registerModule(new MissionEngineModule($env));

        $lifecycle->boot($manifest);

        return new self($health, $lifecycle, $container);
    }
}
