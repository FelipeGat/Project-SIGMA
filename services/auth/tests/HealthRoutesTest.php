<?php

declare(strict_types=1);

namespace Sigma\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Auth\Bootstrap;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Http\HealthEndpoints;

/**
 * `services/auth` passou a expor os três endpoints de ADR-0042 na
 * Release 5.6 (ADR-0094) — até então estava no ar desde a Release 3B
 * sem nenhum deles.
 *
 * O comportamento das respostas em si é testado no Kernel, onde as
 * classes vivem; aqui se prova que **este service** as monta a partir
 * do próprio `HealthManager` e reporta seus Modules reais.
 */
final class HealthRoutesTest extends TestCase
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
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    private function bootWithoutEngines(): Bootstrap
    {
        $path = tempnam(sys_get_temp_dir(), 'sigma-manifest-') . '.yaml';
        file_put_contents($path, self::MANIFEST);
        $this->tempFiles[] = $path;

        return Bootstrap::fromManifestFile($path, ['REDIS_HOST' => 'localhost', 'REDIS_PORT' => '6379']);
    }

    public function test_exposes_the_three_health_endpoints_from_its_own_health_manager(): void
    {
        $health = new HealthEndpoints($this->bootWithoutEngines()->health);

        [$liveStatus, $liveBody] = $health->live();
        [$readyStatus, $readyBody] = $health->ready();
        [$startupStatus] = $health->startup();

        self::assertSame(200, $liveStatus);
        self::assertSame('live', $liveBody['data']['status']);
        self::assertSame(200, $readyStatus);
        self::assertSame('ready', $readyBody['data']['status']);
        self::assertSame(200, $startupStatus);
    }

    public function test_ready_reports_this_services_own_modules(): void
    {
        $health = new HealthEndpoints($this->bootWithoutEngines()->health);

        [, $body] = $health->ready();

        self::assertArrayHasKey('kernel', $body['data']['modules']);
        self::assertArrayHasKey('event-bus', $body['data']['modules']);
        self::assertSame('ready', $body['data']['modules']['kernel']['status']);
    }

    /**
     * A causa real que `BootFailureEndpoints` trata neste service: o
     * Identity Engine derruba o boot quando o MariaDB está fora do ar.
     */
    public function test_booting_with_an_unreachable_database_throws(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sigma-manifest-') . '.yaml';
        file_put_contents($path, <<<YAML
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
            YAML);
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
