<?php

declare(strict_types=1);

namespace Sigma\EventBus;

/**
 * A pequena fatia do cliente Redis de que o Event Bus precisa —
 * isolada em interface própria para que RedisEventBus seja testável
 * sem um servidor Redis real (ver PredisPublisher para a implementação
 * de produção, sobre a biblioteca predis/predis).
 */
interface RedisPublisher
{
    public function publish(string $channel, string $message): int;
}
