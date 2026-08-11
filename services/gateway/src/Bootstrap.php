<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\EventBus\EventBusModule;
use Sigma\IdentityEngine\Interfaces\IdentityEngineModule;
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
 * Monta e inicia o SIGMA a partir do System Manifest — a
 * implementação de referência de `discover → register → boot → start
 * → ready` descrita em BOOTSTRAP.md.
 *
 * A partir da Release 5.5 registra quatro Modules: `kernel` e
 * `event-bus` (Release 2), mais `identity-engine` (para validar a
 * Session de quem chama) e `mission-engine` (para atender às rotas de
 * Mission). O gateway deixa de ser um processo sem estado — é a
 * primeira vez que ele depende de MariaDB, e o `degraded` granular por
 * Module desenhado na Release 2 passa a ter uso real (ver Proposal
 * 5.5, Risco 1).
 */
final class Bootstrap
{
    private function __construct(
        public readonly HealthManager $health,
        public readonly LifecycleManager $lifecycle,
        public readonly IContainer $container,
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
        $lifecycle->registerModule(new IdentityEngineModule($env));
        $lifecycle->registerModule(new MissionEngineModule($env));

        $lifecycle->boot($manifest);

        return new self($health, $lifecycle, $container);
    }
}
