<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Container;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\HealthManager;
use Sigma\Kernel\LifecycleManager;
use Sigma\Kernel\Logger;
use Sigma\Kernel\Manifest\SystemManifestLoader;
use Sigma\Kernel\Tests\Fixtures\TestModule;

/**
 * Cobre os cenários de Scenario Validation listados na proposta da
 * Release 2 (docs/releases/0002-sigma-bootstrap.md).
 */
final class LifecycleManagerTest extends TestCase
{
    private HealthManager $health;

    private LifecycleManager $lifecycle;

    private SystemManifestLoader $manifestLoader;

    protected function setUp(): void
    {
        $this->health = new HealthManager();
        $this->lifecycle = new LifecycleManager(new Container(), $this->health, new Logger(fopen('php://memory', 'wb')));
        $this->manifestLoader = new SystemManifestLoader();
    }

    public function test_boots_modules_in_dependency_order(): void
    {
        $order = [];
        $kernel = new TestModule('kernel', onBoot: function () use (&$order) { $order[] = 'kernel'; });
        $eventBus = new TestModule('event-bus', dependsOn: ['kernel'], onBoot: function () use (&$order) { $order[] = 'event-bus'; });

        $this->lifecycle->registerModule($eventBus);
        $this->lifecycle->registerModule($kernel);

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [kernel, event-bus]
            YAML);

        $this->lifecycle->boot($manifest);

        self::assertSame(['kernel', 'event-bus'], $order);
        self::assertTrue($kernel->booted);
        self::assertTrue($eventBus->booted);
    }

    public function test_system_boots_with_no_optional_modules_present(): void
    {
        $this->lifecycle->registerModule(new TestModule('kernel'));

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules:
              - name: kernel
              - name: telegram
                optional: true
            YAML);

        $this->lifecycle->boot($manifest);

        self::assertTrue($this->health->isReady());
    }

    public function test_system_boots_with_an_absent_optional_module(): void
    {
        $this->lifecycle->registerModule(new TestModule('kernel'));
        // "telegram" declarado no Manifest como opcional, mas nunca registrado.

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules:
              - name: kernel
              - name: telegram
                optional: true
            YAML);

        $this->lifecycle->boot($manifest);

        self::assertTrue($this->health->isReady());
        self::assertArrayNotHasKey('telegram', $this->health->snapshot());
    }

    public function test_a_missing_required_module_prevents_boot(): void
    {
        $this->lifecycle->registerModule(new TestModule('kernel'));
        // "event-bus" é obrigatório no Manifest, mas não foi registrado.

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [kernel, event-bus]
            YAML);

        try {
            $this->lifecycle->boot($manifest);
            self::fail('Esperava SigmaException por módulo obrigatório ausente.');
        } catch (SigmaException $exception) {
            self::assertSame('lifecycle.required_module_missing', $exception->errorCode());
        }

        self::assertFalse($this->health->isReady());
    }

    public function test_a_module_can_be_marked_degraded_after_ready_without_affecting_others(): void
    {
        $kernel = new TestModule('kernel');
        $telegram = new TestModule('telegram');

        $this->lifecycle->registerModule($kernel);
        $this->lifecycle->registerModule($telegram);

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [kernel, telegram]
            YAML);

        $this->lifecycle->boot($manifest);
        self::assertTrue($this->health->isReady());

        // O Telegram cai depois de já estar operando — não é mais uma falha de boot.
        $this->health->report('telegram', ModuleStatus::Degraded, 'API do Telegram fora do ar');

        self::assertFalse($this->health->isReady());
        self::assertSame('ready', $this->health->snapshot()['kernel']['status']);
        self::assertSame('degraded', $this->health->snapshot()['telegram']['status']);
    }

    public function test_an_incompatible_module_version_is_rejected_before_ready(): void
    {
        $this->lifecycle->registerModule(new TestModule('event-bus', version: '0.9.0'));

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules:
              - name: event-bus
                minVersion: "1.0.0"
            YAML);

        try {
            $this->lifecycle->boot($manifest);
            self::fail('Esperava SigmaException por versão incompatível.');
        } catch (SigmaException $exception) {
            self::assertSame('lifecycle.incompatible_module_version', $exception->errorCode());
        }

        self::assertFalse($this->health->isReady());
    }

    public function test_a_circular_dependency_is_detected_and_rejected(): void
    {
        $this->lifecycle->registerModule(new TestModule('a', dependsOn: ['b']));
        $this->lifecycle->registerModule(new TestModule('b', dependsOn: ['a']));

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [a, b]
            YAML);

        try {
            $this->lifecycle->boot($manifest);
            self::fail('Esperava SigmaException por dependência circular.');
        } catch (SigmaException $exception) {
            self::assertSame('lifecycle.circular_dependency', $exception->errorCode());
        }
    }

    public function test_a_module_that_fails_to_boot_blocks_ready_and_is_reported_as_failed(): void
    {
        $kernel = new TestModule('kernel');
        $broken = new TestModule('broken', onBoot: function (): void {
            throw new \RuntimeException('conexão recusada');
        });

        $this->lifecycle->registerModule($kernel);
        $this->lifecycle->registerModule($broken);

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [kernel, broken]
            YAML);

        try {
            $this->lifecycle->boot($manifest);
            self::fail('Esperava SigmaException por falha no boot do Module.');
        } catch (SigmaException $exception) {
            self::assertSame('lifecycle.module_boot_failed', $exception->errorCode());
        }

        self::assertSame('failed', $this->health->snapshot()['broken']['status']);
        self::assertFalse($this->health->isReady());
    }

    public function test_shutdown_runs_in_reverse_boot_order(): void
    {
        $this->lifecycle->registerModule(new TestModule('kernel'));
        $this->lifecycle->registerModule(new TestModule('event-bus', dependsOn: ['kernel']));

        $manifest = $this->manifestLoader->loadFromString(<<<YAML
            project: SIGMA
            version: "1.0"
            modules: [kernel, event-bus]
            YAML);

        $this->lifecycle->boot($manifest);
        $this->lifecycle->shutdown();

        self::assertSame('shutdown', $this->health->snapshot()['event-bus']['status']);
        self::assertSame('shutdown', $this->health->snapshot()['kernel']['status']);
    }
}
