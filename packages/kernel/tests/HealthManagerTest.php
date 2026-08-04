<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\HealthManager;

final class HealthManagerTest extends TestCase
{
    public function test_is_not_ready_before_any_module_reports(): void
    {
        $health = new HealthManager();

        self::assertFalse($health->isReady());
    }

    public function test_is_ready_only_when_every_reported_module_is_ready(): void
    {
        $health = new HealthManager();

        $health->report('kernel', ModuleStatus::Ready);
        self::assertTrue($health->isReady());

        $health->report('event-bus', ModuleStatus::Start);
        self::assertFalse($health->isReady());

        $health->report('event-bus', ModuleStatus::Ready);
        self::assertTrue($health->isReady());
    }

    public function test_a_degraded_module_makes_the_system_not_ready_without_failing_the_others(): void
    {
        $health = new HealthManager();

        $health->report('kernel', ModuleStatus::Ready);
        $health->report('telegram', ModuleStatus::Degraded, 'timeout ao conectar');

        self::assertFalse($health->isReady());

        $snapshot = $health->snapshot();
        self::assertSame('ready', $snapshot['kernel']['status']);
        self::assertSame('degraded', $snapshot['telegram']['status']);
        self::assertSame('timeout ao conectar', $snapshot['telegram']['reason']);
    }

    public function test_live_is_always_true_once_the_process_exists(): void
    {
        self::assertTrue((new HealthManager())->isLive());
    }

    public function test_startup_completion_is_reported_independently_of_ready(): void
    {
        $health = new HealthManager();

        self::assertFalse($health->isStartupComplete());

        $health->markStartupComplete();

        self::assertTrue($health->isStartupComplete());
        self::assertFalse($health->isReady());
    }
}
