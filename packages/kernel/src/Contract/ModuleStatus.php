<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Estados do Lifecycle estendido (ver BOOTSTRAP.md e ADR-0041).
 * `Degraded` não é uma etapa linear — um Module pode assumi-la a
 * qualquer momento depois de `Ready`, sem que isso reordene o ciclo.
 */
enum ModuleStatus: string
{
    case Boot = 'boot';
    case Start = 'start';
    case Ready = 'ready';
    case Degraded = 'degraded';
    case Shutdown = 'shutdown';
    case Failed = 'failed';
}
