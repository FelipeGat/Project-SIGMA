<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Gateway\Bootstrap;

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
        YAML;

    /** @var string[] */
    private array $tempFiles = [];

    public function test_boots_kernel_and_event_bus_from_the_manifest_and_reaches_ready(): void
    {
        $path = $this->writeManifest(self::MANIFEST);

        $bootstrap = Bootstrap::fromManifestFile($path, ['REDIS_HOST' => 'localhost', 'REDIS_PORT' => '6379']);

        self::assertTrue($bootstrap->health->isReady());
        self::assertTrue($bootstrap->health->isStartupComplete());
        self::assertSame('ready', $bootstrap->health->snapshot()['kernel']['status']);
        self::assertSame('ready', $bootstrap->health->snapshot()['event-bus']['status']);
    }

    public function test_fails_when_the_manifest_requires_an_incompatible_event_bus_version(): void
    {
        $path = $this->writeManifest(<<<YAML
            manifestVersion: 1
            project: SIGMA
            version: "1.0"
            modules:
              - name: kernel
              - name: event-bus
                minVersion: "9.9.9"
            YAML);

        $this->expectException(SigmaException::class);

        Bootstrap::fromManifestFile($path, ['REDIS_HOST' => 'localhost', 'REDIS_PORT' => '6379']);
    }

    public function test_fails_when_the_manifest_file_does_not_exist(): void
    {
        $this->expectException(SigmaException::class);

        Bootstrap::fromManifestFile(__DIR__ . '/does-not-exist.yaml', []);
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
        $path = tempnam(sys_get_temp_dir(), 'sigma-manifest-') . '.yaml';
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }
}
