<?php

declare(strict_types=1);

namespace Sigma\EventBus;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\InMemoryEventBus;

/**
 * Implementação de IEventBus com alcance entre processos — infraestrutura
 * apenas, nenhum evento de domínio nesta Release (ver EVENT_MODEL.md,
 * ADR-0053). Toda publicação vai para o canal Redis correspondente
 * (entrega entre processos) e, por composição com InMemoryEventBus,
 * também para qualquer handler registrado no mesmo processo — útil
 * enquanto não existe nenhum Engine/Plugin em processo separado
 * consumindo eventos de fato. Um listener Redis real (cross-processo,
 * via `pubSubLoop`) fica para quando houver um consumidor de verdade —
 * registrado como simplificação conhecida no Decision Log da Release 2
 * e revisitado no ADR-0057.
 */
final class RedisEventBus implements IEventBus
{
    private readonly InMemoryEventBus $local;

    public function __construct(private readonly RedisPublisher $redis)
    {
        $this->local = new InMemoryEventBus();
    }

    public function publish(string $event, array $payload = []): void
    {
        $this->redis->publish($event, json_encode($payload, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        $this->local->publish($event, $payload);
    }

    public function subscribe(string $event, callable $handler): void
    {
        $this->local->subscribe($event, $handler);
    }

    /**
     * Invoca os handlers já registrados localmente via `subscribe()`
     * para um evento **recebido de fora** (via `RedisSubscriber`, de
     * outro processo) — nunca publica de volta no Redis, ao contrário
     * de `publish()`. Sem este método, um worker cross-processo não
     * tinha como entregar uma mensagem recebida aos handlers já
     * registrados no mesmo processo sem causar eco (ADR-0057 previa
     * esta lacuna; resolvida na Release 4B).
     *
     * @param array<string, mixed> $payload
     */
    public function dispatchLocally(string $event, array $payload): void
    {
        $this->local->publish($event, $payload);
    }
}
