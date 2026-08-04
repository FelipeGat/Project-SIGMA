<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\IHealth;
use Sigma\Kernel\Contract\ILogger;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\HealthManager;
use Sigma\Kernel\KernelModule;
use Sigma\Kernel\Logger;

final class KernelModuleTest extends TestCase
{
    public function test_registers_logger_health_and_container_bindings(): void
    {
        $logger = new Logger(fopen('php://memory', 'wb'));
        $health = new HealthManager();
        $module = new KernelModule($logger, $health);
        $container = new Container();

        $module->register($container);

        self::assertSame($logger, $container->get(ILogger::class));
        self::assertSame($health, $container->get(IHealth::class));
    }

    public function test_describes_itself_as_a_package(): void
    {
        $module = new KernelModule(new Logger(fopen('php://memory', 'wb')), new HealthManager());

        self::assertSame(ModuleKind::Package, $module->describe()->type);
        self::assertSame(ModuleStatus::Boot, $module->describe()->status);

        $module->boot();

        self::assertSame(ModuleStatus::Ready, $module->describe()->status);
    }
}
