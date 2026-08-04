<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Kernel\Contract\ILogger;

/**
 * Implementação de referência de ILogger — uma linha JSON estruturada
 * por evento, escrita no stream informado (STDOUT por padrão). Não é
 * "um logger de texto" — é o primeiro pilar de Telemetry (ver
 * TELEMETRY.md e ADR-0043); Metrics/Tracing/Audit chegam junto com o
 * primeiro Engine real que tiver o que medir/rastrear/auditar.
 */
final class Logger implements ILogger
{
    /** @var resource */
    private $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        $this->stream = $stream ?? fopen('php://stdout', 'wb');
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $line = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => $level,
            'message' => $message,
        ] + $context;

        fwrite($this->stream, json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . \PHP_EOL);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
}
