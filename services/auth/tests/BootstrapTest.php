<?php

declare(strict_types=1);

namespace Sigma\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Auth\Bootstrap;
use Sigma\Core\SigmaException;

/**
 * Requer MariaDB alcançável (mesmas variáveis de ambiente do Module
 * real) — pulado explicitamente se não houver, nunca falha por engano.
 * Não requer Redis: EventBusModule conecta de forma lazy (Predis), só
 * na primeira publish/subscribe — ver AuthEndpointsTest para o
 * comportamento quando Redis está inalcançável.
 */
final class BootstrapTest extends TestCase
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
            minVersion: "1.0.0"
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    public function test_boots_kernel_event_bus_and_identity_engine_and_reaches_ready(): void
    {
        $path = $this->writeManifest(self::MANIFEST);

        try {
            $bootstrap = Bootstrap::fromManifestFile($path, $this->dbEnv());
        } catch (SigmaException $exception) {
            self::markTestSkipped('MariaDB não alcançável para testes de services/auth: ' . $exception->getMessage());
        }

        self::assertTrue($bootstrap->health->isReady());
        self::assertSame('ready', $bootstrap->health->snapshot()['identity-engine']['status']);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        $this->tempFiles = [];
    }

    private function writeManifest(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sigma-auth-manifest-') . '.yaml';
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    /** @return array<string, string> */
    private function dbEnv(): array
    {
        return [
            'DB_HOST' => getenv('IDENTITY_TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('IDENTITY_TEST_DB_PORT') ?: '3306',
            'DB_NAME' => getenv('IDENTITY_TEST_DB_NAME') ?: 'sigma_identity_test',
            'DB_USER' => getenv('IDENTITY_TEST_DB_USER') ?: 'root',
            'DB_PASSWORD' => getenv('IDENTITY_TEST_DB_PASSWORD') ?: '',
            'REDIS_HOST' => 'localhost',
            'REDIS_PORT' => '6379',
        ];
    }
}
