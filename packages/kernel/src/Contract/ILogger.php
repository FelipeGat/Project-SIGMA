<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * Logs estruturados, correlacionados — parte da Telemetry inicializada
 * desde o primeiro `boot` (ver ADR-0043), não um logger de texto solto.
 */
interface ILogger
{
    /**
     * @param array<string, mixed> $context Deve incluir `correlationId`/`requestId`
     *   quando disponíveis, para permitir correlação (ver SIGMA_PROTOCOL.md §1).
     */
    public function log(string $level, string $message, array $context = []): void;

    public function debug(string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;
}
