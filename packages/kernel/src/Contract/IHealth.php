<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Onde um Module reporta o próprio estado (`ready`/`degraded`/...) —
 * nunca o Kernel decidindo por ele. Ver BOOTSTRAP.md § Kernel API.
 */
interface IHealth
{
    public function report(string $module, ModuleStatus $status, ?string $reason = null): void;
}
