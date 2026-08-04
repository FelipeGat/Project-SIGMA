<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * O identificador estável que atravessa toda a cadeia Intent→Missions
 * relacionadas (SIGMA_PROTOCOL.md §1) — nesta Release, fornecido por
 * quem cria a Mission (não gerado a partir de uma Intent que ainda
 * não existe, ver MISSION_MODEL.md).
 */
final class CorrelationId extends Identifier
{
}
