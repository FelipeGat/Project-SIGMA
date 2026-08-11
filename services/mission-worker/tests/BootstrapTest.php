<?php

declare(strict_types=1);

namespace Sigma\MissionWorker\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MissionWorker\Bootstrap;

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
          - name: mission-engine
            kind: engine
            minVersion: "1.0.0"
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    public function test_boots_kernel_event_bus_and_mission_engine_and_reaches_ready(): void
    {
        $path = $this->writeManifest(self::MANIFEST);

        try {
            $bootstrap = Bootstrap::fromManifestFile($path, $this->dbEnv());
        } catch (SigmaException $exception) {
            self::markTestSkipped('MariaDB não alcançável para testes de services/mission-worker: ' . $exception->getMessage());
        }

        self::assertTrue($bootstrap->health->isReady());
        self::assertSame('ready', $bootstrap->health->snapshot()['mission-engine']['status']);
    }

    /**
     * O que este worker existe para fazer: o Module precisa expor
     * `CreateMissionFromPlannedEvent` no container, que é o que
     * `bin/worker.php` resolve para ligar ao canal `mission.planned`.
     * Sem isto, o processo subiria e nunca consumiria nada.
     */
    public function test_the_container_exposes_the_use_case_the_worker_subscribes_with(): void
    {
        $path = $this->writeManifest(self::MANIFEST);

        try {
            $bootstrap = Bootstrap::fromManifestFile($path, $this->dbEnv());
        } catch (SigmaException $exception) {
            self::markTestSkipped('MariaDB não alcançável para testes de services/mission-worker: ' . $exception->getMessage());
        }

        self::assertInstanceOf(
            \Sigma\MissionEngine\Application\UseCase\CreateMissionFromPlannedEvent::class,
            $bootstrap->container->get(\Sigma\MissionEngine\Application\UseCase\CreateMissionFromPlannedEvent::class),
        );
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
        $path = tempnam(sys_get_temp_dir(), 'sigma-mission-worker-manifest-') . '.yaml';
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    /** @return array<string, string> */
    private function dbEnv(): array
    {
        return [
            'DB_HOST' => getenv('MISSION_TEST_DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => getenv('MISSION_TEST_DB_PORT') ?: '3306',
            'DB_NAME' => getenv('MISSION_TEST_DB_NAME') ?: 'sigma_mission_test',
            'DB_USER' => getenv('MISSION_TEST_DB_USER') ?: 'root',
            'DB_PASSWORD' => getenv('MISSION_TEST_DB_PASSWORD') ?: '',
            'REDIS_HOST' => 'localhost',
            'REDIS_PORT' => '6379',
        ];
    }
}
