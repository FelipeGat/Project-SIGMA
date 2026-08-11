<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Gateway\BootFailureEndpoints;
use Sigma\Gateway\Bootstrap;

/**
 * O comportamento do gateway quando o boot falha — o achado da Release
 * 5.5 (ver BootFailureEndpoints e o Decision Log): ao ganhar dependência
 * de MariaDB, uma falha de boot deixou de significar "configuração
 * quebrada" e passou a poder significar "o banco piscou".
 */
final class BootFailureEndpointsTest extends TestCase
{
    private const MANIFEST = <<<YAML
        manifestVersion: 1
        project: SIGMA
        version: "1.0"
        modules:
          - name: kernel
            kind: package
          - name: event-bus
            kind: service
            minVersion: "1.0.0"
          - name: identity-engine
            kind: engine
            optional: true
            minVersion: "1.0.0"
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    /**
     * O ponto central: banco fora do ar é `not_ready`, nunca
     * `unresponsive`. `/health/live` responder 503 aqui faria o
     * orquestrador reiniciar em loop um processo saudável, justamente
     * quando a infraestrutura já está sob estresse (BOOTSTRAP.md § Health).
     */
    public function test_live_stays_200_so_the_orchestrator_does_not_restart_a_healthy_process(): void
    {
        $endpoints = new BootFailureEndpoints(new SigmaException('banco inalcançável', 'identity_engine.database_unreachable'));

        [$status, $body] = $endpoints->live();

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('live', $body['data']['status']);
    }

    public function test_ready_and_startup_report_the_boot_failure(): void
    {
        $endpoints = new BootFailureEndpoints(new SigmaException('banco inalcançável', 'identity_engine.database_unreachable'));

        [$readyStatus, $readyBody] = $endpoints->ready();
        [$startupStatus] = $endpoints->startup();

        self::assertSame(503, $readyStatus);
        self::assertSame(503, $startupStatus);
        self::assertSame('bootstrap.failed', $readyBody['error']['code']);
        self::assertStringContainsString('banco inalcançável', $readyBody['error']['message']);
    }

    public function test_domain_routes_report_unavailable_while_the_boot_has_not_completed(): void
    {
        $endpoints = new BootFailureEndpoints(new SigmaException('banco inalcançável', 'identity_engine.database_unreachable'));

        [$status, $body] = $endpoints->unavailable();

        self::assertSame(503, $status);
        self::assertSame('gateway.unavailable', $body['error']['code']);
    }

    /**
     * Prova a causa real, não só a resposta: com MariaDB inalcançável, o
     * boot do gateway de fato lança — é o que `public/index.php` captura.
     */
    public function test_booting_with_an_unreachable_database_throws(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sigma-manifest-') . '.yaml';
        file_put_contents($path, self::MANIFEST);
        $this->tempFiles[] = $path;

        $this->expectException(SigmaException::class);

        Bootstrap::fromManifestFile($path, [
            'REDIS_HOST' => 'localhost',
            'REDIS_PORT' => '6379',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '9', // porta reservada "discard" — sempre recusa conexão
            'DB_NAME' => 'inexistente',
            'DB_USER' => 'inexistente',
            'DB_PASSWORD' => 'inexistente',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        $this->tempFiles = [];
    }
}
