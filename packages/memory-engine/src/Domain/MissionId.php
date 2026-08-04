<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * Referência opaca a uma Mission (Mission Engine, ainda sem Domain
 * próprio) — a Mission que origina uma observação. O Memory Engine só
 * carrega este identificador, nunca resolve a Mission em si.
 */
final class MissionId extends Identifier
{
}
