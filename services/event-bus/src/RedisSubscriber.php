<?php

declare(strict_types=1);

namespace Sigma\EventBus;

use Predis\Client;

/**
 * O primeiro listener Redis cross-processo real do projeto — a
 * lacuna sinalizada desde a Release 2 e formalizada em ADR-0057
 * ("fica para quando houver um consumidor de verdade"). Bloqueia
 * indefinidamente em `pubSubLoop()`, traduzindo cada mensagem
 * recebida do canal Redis numa chamada a `$onMessage` — normalmente
 * `RedisEventBus::dispatchLocally()`, para que os handlers já
 * registrados via `IEventBus::subscribe()` no mesmo processo sejam
 * invocados sem publicar de volta no Redis (o que causaria eco).
 *
 * Sem retry/dead-letter/reconexão sofisticada nesta Release — um loop
 * simples é suficiente para o único consumidor real que existe
 * (`services/memory-worker`); hardening de mensageria é escopo da
 * Release 23 (Production Hardening).
 */
final class RedisSubscriber
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @param list<string> $events
     * @param callable(string $event, array<string, mixed> $payload): void $onMessage
     */
    public function listen(array $events, callable $onMessage): never
    {
        $pubSub = $this->client->pubSubLoop();
        $pubSub->subscribe(...$events);

        foreach ($pubSub as $message) {
            self::handleMessage($message, $onMessage);
        }

        // pubSubLoop() só termina se subscribe()/unsubscribe() zerar os
        // canais assinados — não acontece neste método, mas o tipo de
        // retorno `never` exige uma saída explícita se o loop parar.
        exit(0);
    }

    /**
     * A lógica de uma mensagem isolada — extraída de `listen()` para
     * ser testável sem uma conexão Redis real (`$message` só precisa
     * ter `kind`/`channel`/`payload`, mesma forma que o Predis produz
     * em `pubSubLoop()`, ver vendor/predis/predis/src/PubSub/). A
     * cobertura de que o loop bloqueante em si entrega mensagens
     * cross-processo de verdade vem da Scenario Validation via Docker,
     * não de um teste de unidade (ver Validation Report da 4B).
     *
     * @param object{kind: string, channel: string, payload: string} $message
     * @param callable(string $event, array<string, mixed> $payload): void $onMessage
     */
    public static function handleMessage(object $message, callable $onMessage): void
    {
        if ($message->kind !== 'message') {
            return;
        }

        $payload = json_decode($message->payload, true);
        $onMessage($message->channel, is_array($payload) ? $payload : []);
    }
}
