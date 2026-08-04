<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * Referência opaca a um Tenant do Identity Engine — o Memory Engine
 * nunca resolve nem possui um Tenant por conta própria (ADR-0039),
 * só carrega o identificador em toda entidade deste modelo.
 */
final class TenantId extends Identifier
{
}
