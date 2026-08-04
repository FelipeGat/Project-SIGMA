<?php

declare(strict_types=1);

namespace Sigma\MemoryWorker\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MemoryWorker\Bootstrap;

/**
 * Requer MariaDB alcançável (mesmas variáveis do Module real) — pulado
 * explicitamente se não houver, mesmo padrão de
 * services/auth/tests/BootstrapTest.php. Não requer Redis: EventBusModule
 * conecta de forma lazy.
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
          - name: memory-engine
            kind: engine
            minVersion: "1.0.0"
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    public function test_boots_kernel_event_bus_and_memory_engine_and_reaches_ready(): void
    {
        $path = $this->writeManifest(self::MANIFEST);

        try {
            $bootstrap = Bootstrap::fromManifestFile($path, $this->dbEnv());
        } catch (SigmaException $exception) {
            self::markTestSkipped('MariaDB não alcançável para testes de services/memory-worker: ' . $exception->getMessage());
        }

        self::assertTrue($bootstrap->health->isReady());
        self::assertSame('ready', $bootstrap->health->snapshot()['memory-engine']['status']);
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
        $path = tempnam(sys_get_temp_dir(), 'sigma-memory-worker-manifest-') . '.yaml';
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    /** @return array<string, string> */
    private function dbEnv(): array
    {
        return [
            'DB_HOST' => getenv('MEMORY_TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('MEMORY_TEST_DB_PORT') ?: '3306',
            'DB_NAME' => getenv('MEMORY_TEST_DB_NAME') ?: 'sigma_memory_test',
            'DB_USER' => getenv('MEMORY_TEST_DB_USER') ?: 'root',
            'DB_PASSWORD' => getenv('MEMORY_TEST_DB_PASSWORD') ?: '',
            'REDIS_HOST' => 'localhost',
            'REDIS_PORT' => '6379',
        ];
    }
}
