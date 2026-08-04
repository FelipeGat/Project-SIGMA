<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests\Manifest;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Manifest\SystemManifestLoader;

final class SystemManifestLoaderTest extends TestCase
{
    private SystemManifestLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new SystemManifestLoader();
    }

    public function test_parses_a_valid_manifest(): void
    {
        $manifest = $this->loader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules:
              - name: kernel
                kind: package
              - name: event-bus
                kind: service
                optional: false
                minVersion: "1.0.0"
            providers: []
            workspace: []
            YAML);

        self::assertSame('SIGMA', $manifest->project);
        self::assertSame('1.0', $manifest->version);
        self::assertCount(2, $manifest->modules);
        self::assertTrue($manifest->has('kernel'));
        self::assertTrue($manifest->has('event-bus'));
        self::assertSame(ModuleKind::Service, $manifest->modules[1]->kind);
        self::assertSame('1.0.0', $manifest->modules[1]->minVersion);
    }

    public function test_accepts_a_bare_module_name_as_a_required_package_by_default(): void
    {
        $manifest = $this->loader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules:
              - kernel
            YAML);

        self::assertSame('kernel', $manifest->modules[0]->name);
        self::assertSame(ModuleKind::Package, $manifest->modules[0]->kind);
        self::assertFalse($manifest->modules[0]->optional);
    }

    public function test_rejects_malformed_yaml(): void
    {
        $this->expectException(SigmaException::class);
        $this->expectExceptionCode(0);

        try {
            $this->loader->loadFromString("project: SIGMA\n  version: [unbalanced");
        } catch (SigmaException $exception) {
            self::assertSame('manifest.invalid_yaml', $exception->errorCode());

            throw $exception;
        }
    }

    public function test_rejects_a_manifest_missing_required_fields(): void
    {
        $this->expectException(SigmaException::class);

        try {
            $this->loader->loadFromString("modules: []\n");
        } catch (SigmaException $exception) {
            self::assertSame('manifest.missing_field', $exception->errorCode());

            throw $exception;
        }
    }

    public function test_rejects_a_module_entry_without_a_name(): void
    {
        $this->expectException(SigmaException::class);

        try {
            $this->loader->loadFromString(<<<YAML
                project: SIGMA
                version: "1.0"
                modules:
                  - kind: engine
                YAML);
        } catch (SigmaException $exception) {
            self::assertSame('manifest.invalid_module_entry', $exception->errorCode());

            throw $exception;
        }
    }

    public function test_rejects_an_unknown_module_kind(): void
    {
        $this->expectException(SigmaException::class);

        try {
            $this->loader->loadFromString(<<<YAML
                project: SIGMA
                version: "1.0"
                modules:
                  - name: mystery
                    kind: not-a-real-kind
                YAML);
        } catch (SigmaException $exception) {
            self::assertSame('manifest.invalid_module_kind', $exception->errorCode());

            throw $exception;
        }
    }

    public function test_fails_when_the_file_does_not_exist(): void
    {
        $this->expectException(SigmaException::class);

        $this->loader->loadFromFile(__DIR__ . '/does-not-exist.yaml');
    }
}
