<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain\Event;

/**
 * Um evento de domínio do Mission Engine — catalogado em
 * MISSION_EVENTS.md. Camada Technical (EVENT_MODEL.md), nunca
 * publicado diretamente pelo Domain (mesmo princípio de ADR-0062) —
 * produzido como valor de retorno, publicado de fato pela Application
 * (Release 5B seguinte, se a Release se dividir).
 */
interface DomainEvent
{
    /** Nome no Event Bus, dot-case — ex: "mission.created". */
    public function name(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
