<?php

declare(strict_types=1);

namespace Sigma\Kernel\Http;

use Sigma\Core\Envelope;
use Sigma\Kernel\HealthManager;

/**
 * A lógica dos três endpoints de health de ADR-0042 — separada de
 * qualquer front controller para ser testável sem subir um servidor
 * HTTP. Ver BOOTSTRAP.md § Health.
 *
 * Vive no Kernel desde a ADR-0094: health é comportamento de Module,
 * não de service, e todo processo deployável do SIGMA expõe os mesmos
 * três endpoints a partir do mesmo `HealthManager`. Traduz estado em
 * pares `[status, Envelope]` — sem roteamento, sem middleware, sem
 * conhecer SAPI, sem conhecer Engine (ADR-0040).
 *
 * @see BootFailureEndpoints Para quando o próprio boot falhou.
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
