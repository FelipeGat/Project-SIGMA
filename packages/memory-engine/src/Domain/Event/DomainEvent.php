<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain\Event;

/**
 * Um evento de domínio do Memory Engine — catalogado em
 * DOMAIN_EVENTS.md. Camada Semantic (EVENT_MODEL.md), nunca publicado
 * diretamente pelo Domain (mesmo princípio de ADR-0062, aplicado ao
 * Memory Engine) — produzido como valor de retorno, publicado de fato
 * pela Application (Release 4B).
 */
interface DomainEvent
{
    /** Nome no Event Bus, dot-case — ex: "memory.record_observed". */
    public function name(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
