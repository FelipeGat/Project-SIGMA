<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * "Quando uma Memory desce" (MEMORY_PROMOTION_RULES.md) — um
 * MemoryRecord nunca é rebaixado de nível em lugar, só marcado como
 * não mais confiável. Active é o estado normal; Deprecated é
 * contradição automática (pode voltar a Active); Retracted só por
 * ação humana explícita, nunca volta sozinho. Ver ADR-0088.
 */
enum MemoryRecordStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Retracted = 'retracted';
}
