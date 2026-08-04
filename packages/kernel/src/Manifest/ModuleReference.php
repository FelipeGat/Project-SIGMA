<?php

declare(strict_types=1);

namespace Sigma\Kernel\Manifest;

use Sigma\Kernel\Contract\ModuleKind;

/**
 * Uma entrada da seção `modules` do System Manifest — o que o Bootstrap
 * deveria carregar, antes de perguntar a qualquer Module se ele existe
 * de fato (ver SYSTEM_MANIFEST.md).
 */
final class ModuleReference
{
    public function __construct(
        public readonly string $name,
        public readonly ModuleKind $kind,
        public readonly bool $optional = false,
        public readonly ?string $minVersion = null,
    ) {
    }
}
