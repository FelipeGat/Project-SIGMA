<?php

declare(strict_types=1);

namespace Sigma\EventBus;

use Sigma\Kernel\Contract\IEventBus;

/**
 * Implementação de referência de IEventBus — infraestrutura apenas,
 * nenhum evento de domínio nesta Release (ver EVENT_MODEL.md,
 * ADR-0053). Toda publicação vai para o canal Redis correspondente
 * (entrega entre processos) **e**, de forma síncrona, para qualquer
 * handler registrado no mesmo processo via `subscribe()` — útil
 * enquanto não existe nenhum Engine/Plugin em processo separado
 * consumindo eventos de fato. Um listener Redis real (cross-processo,
 * via `pubSubLoop`) fica para quando houver um consumidor de verdade —
 * registrado como simplificação conhecida no Decision Log desta Release.
 */
final class RedisEventBus implements IEventBus
{
    /** @var array<string, list<callable(array<string, mixed>): void>> */
    private array $handlers = [];

    public function __construct(private readonly RedisPublisher $redis)
    {
    }

    public function publish(string $event, array $payload = []): void
    {
        $this->redis->publish($event, json_encode($payload, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        foreach ($this->handlers[$event] ?? [] as $handler) {
            $handler($payload);
        }
    }

    public function subscribe(string $event, callable $handler): void
    {
        $this->handlers[$event][] = $handler;
    }
}
