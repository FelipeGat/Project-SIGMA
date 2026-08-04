<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Gateway\HealthEndpoints;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\HealthManager;

final class HealthEndpointsTest extends TestCase
{
    public function test_live_is_always_200_once_the_process_exists(): void
    {
        [$status, $body] = (new HealthEndpoints(new HealthManager()))->live();

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('live', $body['data']['status']);
        self::assertSame('1.0', $body['protocolVersion']);
    }

    public function test_ready_is_503_before_every_module_reports_ready(): void
    {
        $health = new HealthManager();
        $health->report('kernel', ModuleStatus::Boot);

        [$status, $body] = (new HealthEndpoints($health))->ready();

        self::assertSame(503, $status);
        // A chamada em si teve sucesso — ela respondeu corretamente que o
        // sistema não está pronto. `success: false` é reservado para a
        // chamada falhar, não para "a resposta foi um estado negativo".
        self::assertTrue($body['success']);
        self::assertSame('not_ready', $body['data']['status']);
    }

    public function test_ready_is_200_once_every_module_is_ready(): void
    {
        $health = new HealthManager();
        $health->report('kernel', ModuleStatus::Ready);
        $health->report('event-bus', ModuleStatus::Ready);

        [$status, $body] = (new HealthEndpoints($health))->ready();

        self::assertSame(200, $status);
        self::assertSame('ready', $body['data']['status']);
        self::assertArrayHasKey('kernel', $body['data']['modules']);
    }

    public function test_ready_is_503_when_a_module_is_degraded(): void
    {
        $health = new HealthManager();
        $health->report('kernel', ModuleStatus::Ready);
        $health->report('telegram', ModuleStatus::Degraded, 'timeout');

        [$status, $body] = (new HealthEndpoints($health))->ready();

        self::assertSame(503, $status);
        self::assertSame('degraded', $body['data']['modules']['telegram']['status']);
    }

    public function test_startup_reflects_markStartupComplete(): void
    {
        $health = new HealthManager();
        $endpoints = new HealthEndpoints($health);

        [$status, $body] = $endpoints->startup();
        self::assertSame(503, $status);
        self::assertSame('starting', $body['data']['status']);

        $health->markStartupComplete();

        [$status, $body] = $endpoints->startup();
        self::assertSame(200, $status);
        self::assertSame('started', $body['data']['status']);
    }
}
