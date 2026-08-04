<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * Uma transição de `MissionStatus` já ocorrida — `Mission.history` é
 * append-only (MISSION_MODEL.md), nunca editada, só acrescida, mesmo
 * princípio de "eventos não são reescritos" (EVENT_MODEL.md — Regras).
 */
final class MissionHistoryEntry
{
    public function __construct(
        public readonly MissionStatus $status,
        public readonly \DateTimeImmutable $at,
    ) {
    }
}
