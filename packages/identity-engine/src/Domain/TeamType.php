<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * System Team (CTO/Developer/Support) vs. Business Team (Comercial/
 * Financeiro/Obras) — distinção pedida para permitir automações
 * futuras que discriminam por tipo de Team. Ver ADR-0064.
 */
enum TeamType: string
{
    case System = 'system';
    case Business = 'business';
}
