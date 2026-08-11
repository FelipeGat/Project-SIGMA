<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests\Http;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Http\BootFailureEndpoints;

/**
 * O comportamento de todo processo do SIGMA quando o próprio boot falha
 * — achado da Release 5.5, generalizado a todo Module pela
 * [ADR-0094](../../../../docs/adr/0094-health-endpoints-pertencem-ao-kernel.md).
 *
 * A prova de que um Module de verdade *causa* essa falha (MariaDB
 * inalcançável derrubando `register()`) vive em
 * `services/gateway/tests/BootstrapTest.php` — o Kernel não conhece
 * Engine nem service, então não pode testá-la aqui (ADR-0040).
 */
final class BootFailureEndpointsTest extends TestCase
{
    private function endpoints(): BootFailureEndpoints
    {
        return new BootFailureEndpoints(new SigmaException('banco inalcançável', 'identity_engine.database_unreachable'));
    }

    /**
     * O ponto central: dependência externa fora do ar é `not_ready`,
     * nunca `unresponsive`. `/health/live` responder 503 aqui faria o
     * orquestrador reiniciar em loop um processo saudável, justamente
     * quando a infraestrutura já está sob estresse (BOOTSTRAP.md § Health).
     */
    public function test_live_stays_200_so_the_orchestrator_does_not_restart_a_healthy_process(): void
    {
        [$status, $body] = $this->endpoints()->live();

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('live', $body['data']['status']);
    }

    public function test_ready_and_startup_report_the_boot_failure(): void
    {
        [$readyStatus, $readyBody] = $this->endpoints()->ready();
        [$startupStatus] = $this->endpoints()->startup();

        self::assertSame(503, $readyStatus);
        self::assertSame(503, $startupStatus);
        self::assertSame('bootstrap.failed', $readyBody['error']['code']);
        self::assertStringContainsString('banco inalcançável', $readyBody['error']['message']);
    }

    public function test_domain_routes_report_unavailable_while_the_boot_has_not_completed(): void
    {
        [$status, $body] = $this->endpoints()->unavailable();

        self::assertSame(503, $status);
        self::assertSame('service.unavailable', $body['error']['code']);
    }
}
