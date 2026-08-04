<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain\Event;

/**
 * Um evento de domínio do Identity Engine — catalogado em
 * DOMAIN_EVENTS.md. Camada Semantic (EVENT_MODEL.md), nunca publicado
 * diretamente pelo Domain (ver ADR-0062) — produzido como valor de
 * retorno, publicado de fato pela Application (Release 3B).
 */
interface DomainEvent
{
    /** Nome no Event Bus, dot-case — ex: "identity.created". */
    public function name(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
