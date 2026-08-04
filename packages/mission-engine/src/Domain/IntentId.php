<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * Referência opaca à Intent de origem (Intent Engine, Release 7 —
 * ainda sem Domain próprio). `null` é um valor válido e esperado
 * durante toda a Release 5/6 (ver MISSION_MODEL.md).
 */
final class IntentId extends Identifier
{
}
