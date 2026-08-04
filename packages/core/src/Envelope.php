<?php

declare(strict_types=1);

namespace Sigma\Core;

/**
 * O Envelope do SIGMA Protocol (ver SIGMA_PROTOCOL.md §1) — toda
 * resposta HTTP de qualquer serviço do SIGMA sai neste formato.
 * Movido de services/gateway para cá (ver ADR-0069) quando
 * services/auth passou a precisar do mesmo formato — evita duplicar
 * um formato que é, por definição, compartilhado por todo o Protocol.
 */
final class Envelope
{
    private const PROTOCOL_VERSION = '1.0';

    /**
     * @param array<string, mixed>|null $data
     * @param array<int, string> $events
     * @param array<int, mixed> $warnings
     */
    public static function success(?array $data = null, array $events = [], array $warnings = []): array
    {
        return self::build(success: true, data: $data, error: null, events: $events, warnings: $warnings);
    }

    public static function failure(string $code, string $message, ?array $data = null): array
    {
        return self::build(success: false, data: $data, error: ['code' => $code, 'message' => $message]);
    }

    /**
     * @param array<string, mixed>|null $data
     * @param array{code: string, message: string}|null $error
     * @param array<int, string> $events
     * @param array<int, mixed> $warnings
     * @param array<int, mixed> $nextActions
     */
    private static function build(
        bool $success,
        ?array $data,
        ?array $error,
        array $events = [],
        array $warnings = [],
        array $nextActions = [],
    ): array {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'correlationId' => Id::generate(),
            'requestId' => Id::generate(),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'missionId' => null,
            'workspaceId' => null,
            'actor' => ['type' => 'system', 'id' => 'sigma-bootstrap'],
            'intent' => null,
            'capability' => null,
            'success' => $success,
            'data' => $data,
            'events' => $events,
            'memory' => [],
            'warnings' => $warnings,
            'error' => $error,
            'audit' => ['riskLevel' => 'read', 'autonomyLevelRequired' => 0, 'autonomyLevelEffective' => 0],
            'nextActions' => $nextActions,
        ];
    }
}
