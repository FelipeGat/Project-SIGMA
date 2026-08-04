<?php

declare(strict_types=1);

namespace Sigma\EventBus\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\EventBus\RedisEventBus;
use Sigma\EventBus\RedisPublisher;

final class RedisEventBusTest extends TestCase
{
    public function test_publish_sends_the_encoded_payload_to_the_redis_channel(): void
    {
        $redis = new class implements RedisPublisher {
            public array $published = [];

            public function publish(string $channel, string $message): int
            {
                $this->published[] = [$channel, $message];

                return 1;
            }
        };

        $bus = new RedisEventBus($redis);
        $bus->publish('mission.requested', ['missionId' => 'm-1']);

        self::assertSame('mission.requested', $redis->published[0][0]);
        self::assertSame(['missionId' => 'm-1'], json_decode($redis->published[0][1], true));
    }

    public function test_local_subscribers_receive_the_payload_synchronously(): void
    {
        $redis = new class implements RedisPublisher {
            public function publish(string $channel, string $message): int
            {
                return 1;
            }
        };

        $bus = new RedisEventBus($redis);
        $received = null;

        $bus->subscribe('mission.requested', function (array $payload) use (&$received): void {
            $received = $payload;
        });

        $bus->publish('mission.requested', ['missionId' => 'm-2']);

        self::assertSame(['missionId' => 'm-2'], $received);
    }

    public function test_a_subscriber_of_a_different_event_is_not_called(): void
    {
        $redis = new class implements RedisPublisher {
            public function publish(string $channel, string $message): int
            {
                return 1;
            }
        };

        $bus = new RedisEventBus($redis);
        $called = false;

        $bus->subscribe('other.event', function () use (&$called): void {
            $called = true;
        });

        $bus->publish('mission.requested', []);

        self::assertFalse($called);
    }

    /**
     * dispatchLocally() é o método novo da Release 4B — entrega uma
     * mensagem já recebida de outro processo (via RedisSubscriber) aos
     * handlers locais, sem nunca publicar de volta no Redis (o que
     * causaria eco). Ver ADR-0057 e o Decision Log da 4B.
     */
    public function test_dispatch_locally_invokes_subscribers_without_publishing_to_redis(): void
    {
        $redis = new class implements RedisPublisher {
            public array $published = [];

            public function publish(string $channel, string $message): int
            {
                $this->published[] = [$channel, $message];

                return 1;
            }
        };

        $bus = new RedisEventBus($redis);
        $received = null;

        $bus->subscribe('identity.created', function (array $payload) use (&$received): void {
            $received = $payload;
        });

        $bus->dispatchLocally('identity.created', ['identityId' => 'abc']);

        self::assertSame(['identityId' => 'abc'], $received);
        self::assertSame([], $redis->published, 'dispatchLocally nunca deve publicar de volta no Redis');
    }
}
