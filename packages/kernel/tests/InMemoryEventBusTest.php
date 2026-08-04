<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;

final class InMemoryEventBusTest extends TestCase
{
    public function test_a_subscriber_receives_the_payload_published_to_its_event(): void
    {
        $bus = new InMemoryEventBus();
        $received = null;

        $bus->subscribe('mission.requested', function (array $payload) use (&$received): void {
            $received = $payload;
        });

        $bus->publish('mission.requested', ['missionId' => 'm-1']);

        self::assertSame(['missionId' => 'm-1'], $received);
    }

    public function test_a_subscriber_of_a_different_event_is_not_called(): void
    {
        $bus = new InMemoryEventBus();
        $called = false;

        $bus->subscribe('other.event', function () use (&$called): void {
            $called = true;
        });

        $bus->publish('mission.requested', []);

        self::assertFalse($called);
    }

    public function test_publish_without_subscribers_does_not_fail(): void
    {
        $bus = new InMemoryEventBus();

        $bus->publish('mission.requested', ['missionId' => 'm-1']);

        $this->expectNotToPerformAssertions();
    }

    public function test_multiple_subscribers_of_the_same_event_all_receive_the_payload(): void
    {
        $bus = new InMemoryEventBus();
        $calls = [];

        $bus->subscribe('mission.requested', function (array $payload) use (&$calls): void {
            $calls[] = 'first:' . $payload['missionId'];
        });
        $bus->subscribe('mission.requested', function (array $payload) use (&$calls): void {
            $calls[] = 'second:' . $payload['missionId'];
        });

        $bus->publish('mission.requested', ['missionId' => 'm-1']);

        self::assertSame(['first:m-1', 'second:m-1'], $calls);
    }
}
