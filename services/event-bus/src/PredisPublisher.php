<?php

declare(strict_types=1);

namespace Sigma\EventBus;

use Predis\Client;

/**
 * Implementação de produção de RedisPublisher, sobre predis/predis.
 */
final class PredisPublisher implements RedisPublisher
{
    public function __construct(private readonly Client $client)
    {
    }

    public function publish(string $channel, string $message): int
    {
        return (int) $this->client->publish($channel, $message);
    }
}
