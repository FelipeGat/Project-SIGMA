<?php

declare(strict_types=1);

namespace Sigma\EventBus\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\EventBus\RedisSubscriber;

/**
 * `handleMessage()` é a lógica de uma mensagem isolada, extraída de
 * `listen()` para não depender de uma conexão Redis real neste teste
 * — o loop bloqueante em si é provado via Scenario Validation com
 * Docker real (ver Validation Report da Release 4B), não aqui.
 */
final class RedisSubscriberTest extends TestCase
{
    public function test_a_message_kind_is_decoded_and_dispatched(): void
    {
        $received = null;
        $message = (object) ['kind' => 'message', 'channel' => 'identity.created', 'payload' => '{"identityId":"abc"}'];

        RedisSubscriber::handleMessage($message, function (string $event, array $payload) use (&$received): void {
            $received = [$event, $payload];
        });

        self::assertSame(['identity.created', ['identityId' => 'abc']], $received);
    }

    public function test_a_subscribe_confirmation_is_ignored_not_dispatched(): void
    {
        $called = false;
        $message = (object) ['kind' => 'subscribe', 'channel' => 'identity.created', 'payload' => '1'];

        RedisSubscriber::handleMessage($message, function () use (&$called): void {
            $called = true;
        });

        self::assertFalse($called);
    }

    public function test_a_non_json_payload_dispatches_an_empty_array_instead_of_failing(): void
    {
        $received = null;
        $message = (object) ['kind' => 'message', 'channel' => 'identity.created', 'payload' => 'not json'];

        RedisSubscriber::handleMessage($message, function (string $event, array $payload) use (&$received): void {
            $received = $payload;
        });

        self::assertSame([], $received);
    }
}
