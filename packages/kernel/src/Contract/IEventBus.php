<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Mecanismo técnico de publish/subscribe — nunca o conteúdo de um
 * evento de domínio, que pertence ao Module que o publica (ver
 * EVENT_MODEL.md). Nesta Release, infraestrutura apenas: nenhum
 * evento de domínio é publicado ainda.
 */
interface IEventBus
{
    /**
     * @param array<string, mixed> $payload
     */
    public function publish(string $event, array $payload = []): void;

    /**
     * @param callable(array<string, mixed>): void $handler
     */
    public function subscribe(string $event, callable $handler): void;
}
