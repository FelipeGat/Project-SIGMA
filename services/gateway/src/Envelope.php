<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\Core\Id;

/**
 * O Envelope do SIGMA Protocol (ver SIGMA_PROTOCOL.md §1) — toda
 * resposta do Gateway, mesmo um endpoint de infraestrutura como
 * /health/*, sai neste formato.
 */
final class Envelope
{
    private const PROTOCOL_VERSION = '1.0';

    /**
     * @param array<string, mixed>|null $data
     * @param array{code: string, message: string}|null $error
     * @param array<int, string> $events
     * @param array<int, mixed> $warnings
     * @param array<int, mixed> $nextActions
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
