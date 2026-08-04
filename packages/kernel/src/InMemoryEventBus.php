<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Kernel\Contract\IEventBus;

/**
 * Implementação de referência de IEventBus — pub/sub síncrono, em
 * processo único, sem nenhuma dependência de infraestrutura externa.
 * É a implementação padrão para testes e para qualquer Module que
 * não precise de entrega entre processos. Implementações que cruzam
 * processos (RedisEventBus, e no futuro RabbitMQEventBus,
 * KafkaEventBus) dependem desta classe por composição para a entrega
 * local, em vez de reimplementar pub/sub em cada uma (ver ADR-0057).
 */
final class InMemoryEventBus implements IEventBus
{
    /** @var array<string, list<callable(array<string, mixed>): void>> */
    private array $handlers = [];

    public function publish(string $event, array $payload = []): void
    {
        foreach ($this->handlers[$event] ?? [] as $handler) {
            $handler($payload);
        }
    }

    public function subscribe(string $event, callable $handler): void
    {
        $this->handlers[$event][] = $handler;
    }
}
