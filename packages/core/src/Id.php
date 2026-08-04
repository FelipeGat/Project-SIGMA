<?php

declare(strict_types=1);

namespace Sigma\Core;

/**
 * Gera identificadores usados pelo Envelope do SIGMA Protocol
 * (correlationId, requestId) e por qualquer outra entidade que precise
 * de um identificador único. UUID v4, sem dependência externa.
 *
 * @see SIGMA_PROTOCOL.md §1 — O Envelope
 */
final class Id
{
    private function __construct()
    {
        // Classe estática — nunca instanciada.
    }

    public static function generate(): string
    {
        $bytes = random_bytes(16);

        // Marca a versão (4) e a variante (RFC 4122), exigidas pelo formato UUID v4.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
