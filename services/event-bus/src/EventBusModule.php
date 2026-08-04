<?php

declare(strict_types=1);

namespace Sigma\EventBus;

use Predis\Client;
use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\ConfigurationProvider;

/**
 * O Module `event-bus` — um Service (ver ADR-0040: `kind` é metadado,
 * o Kernel não trata este Module de forma especial). Valida configuração
 * e registra `IEventBus` no Container durante `register()`.
 */
final class EventBusModule implements IModule
{
    private const VERSION = '1.0.0';

    private ?RedisEventBus $eventBus = null;

    /**
     * @param array<string, string|null> $env Fonte de configuração — $_ENV por padrão, injetável para testes.
     */
    public function __construct(private readonly array $env = [])
    {
    }

    public function name(): string
    {
        return 'event-bus';
    }

    public function kind(): ModuleKind
    {
        return ModuleKind::Service;
    }

    public function dependsOn(): array
    {
        return ['kernel'];
    }

    public function configSchema(): ConfigSchema
    {
        return new ConfigSchema(
            required: ['REDIS_HOST', 'REDIS_PORT'],
            optional: ['REDIS_PASSWORD'],
        );
    }

    public function register(IContainer $container): void
    {
        // ConfigurationProvider valida a configuração obrigatória já na
        // construção — falha aqui, antes de qualquer Module chegar a
        // `boot()`, conforme ADR-0044 ("falha no boot, não depois").
        $config = new ConfigurationProvider($this->configSchema(), $this->env, $this->name());

        $client = new Client([
            'host' => $config->require('REDIS_HOST'),
            'port' => (int) $config->require('REDIS_PORT'),
            'password' => $config->get('REDIS_PASSWORD'),
        ]);

        $this->eventBus = new RedisEventBus(new PredisPublisher($client));

        $container->bind(IEventBus::class, $this->eventBus);
    }

    public function boot(): void
    {
        // A conexão TCP com o Redis é lazy dentro do cliente Predis —
        // abre de fato na primeira publish/subscribe (etapa `start`).
        // register() já validou que a configuração é resolvível.
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name(),
            type: $this->kind(),
            version: self::VERSION,
            status: $this->eventBus !== null ? ModuleStatus::Ready : ModuleStatus::Boot,
            capabilities: ['Publish', 'Subscribe'],
            dependencies: $this->dependsOn(),
        );
    }
}
