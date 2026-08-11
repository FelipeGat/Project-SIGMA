<?php

declare(strict_types=1);

namespace Sigma\Gateway\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Gateway\Bootstrap;

/**
 * A causa real que `BootFailureEndpoints` existe para tratar: com
 * MariaDB inalcançável, `IdentityEngineModule::register()` lança e
 * derruba o boot inteiro do gateway — é o que `public/index.php`
 * captura.
 *
 * O *comportamento* das respostas nesse estado é testado em
 * `packages/kernel/tests/Http/BootFailureEndpointsTest.php`, onde a
 * classe passou a viver (ADR-0094). Aqui fica só a prova de que um
 * Module de verdade produz a falha — o Kernel não conhece Engine e não
 * poderia testá-la (ADR-0040).
 */
final class BootFailsWithUnreachableDatabaseTest extends TestCase
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
