<?php

declare(strict_types=1);

namespace Sigma\EventBus\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\EventBus\EventBusModule;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\ModuleKind;

final class EventBusModuleTest extends TestCase
{
    public function test_describes_itself_with_publish_and_subscribe_capabilities(): void
    {
        $module = new EventBusModule(['REDIS_HOST' => 'localhost', 'REDIS_PORT' => '6379']);

        $descriptor = $module->describe();

        self::assertSame('event-bus', $descriptor->id);
        self::assertSame(ModuleKind::Service, $descriptor->type);
        self::assertSame(['Publish', 'Subscribe'], $descriptor->capabilities);
        self::assertSame(['kernel'], $descriptor->dependencies);
    }

    public function test_registers_an_ieventbus_binding_in_the_container(): void
    {
        $module = new EventBusModule(['REDIS_HOST' => 'localhost', 'REDIS_PORT' => '6379']);
        $container = new Container();

        $module->register($container);

        self::assertTrue($container->has(IEventBus::class));
        self::assertInstanceOf(IEventBus::class, $container->get(IEventBus::class));
    }

    public function test_fails_at_register_when_required_configuration_is_missing(): void
    {
        $module = new EventBusModule([]);

        $this->expectException(SigmaException::class);
        $this->expectExceptionMessage('event-bus');

        $module->register(new Container());
    }
}
