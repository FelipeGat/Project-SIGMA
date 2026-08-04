<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Metadado, não comportamento — o Kernel trata todo Module da mesma
 * forma independente do valor de `kind`. Nenhuma lógica do Kernel
 * ramifica com base neste enum (ver ADR-0040).
 */
enum ModuleKind: string
{
    case Engine = 'engine';
    case Plugin = 'plugin';
    case Service = 'service';
    case Package = 'package';
}
