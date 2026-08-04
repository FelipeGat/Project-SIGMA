<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\Core\Envelope;
use Sigma\Kernel\HealthManager;

/**
 * A lógica dos três endpoints de health — separada do front controller
 * (`public/index.php`) para ser testável sem subir um servidor HTTP.
 * Ver BOOTSTRAP.md § Health e ADR-0042.
 *
 * @see public/index.php
 */
final class HealthEndpoints
{
    public function __construct(private readonly HealthManager $health)
    {
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function live(): array
    {
        $status = $this->health->isLive() ? 200 : 503;

        return [$status, Envelope::success(['status' => $this->health->isLive() ? 'live' : 'unresponsive'])];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function ready(): array
    {
        $snapshot = $this->health->snapshot();
        $ready = $this->health->isReady();

        return [
            $ready ? 200 : 503,
            Envelope::success([
                'status' => $ready ? 'ready' : 'not_ready',
                'modules' => $snapshot,
            ]),
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function startup(): array
    {
        $complete = $this->health->isStartupComplete();

        return [
            $complete ? 200 : 503,
            Envelope::success(['status' => $complete ? 'started' : 'starting']),
        ];
    }
}
