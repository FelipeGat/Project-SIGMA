<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Interfaces;

use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\PlannerEngine\Application\UseCase\PlanFromIntent;
use Sigma\PlannerEngine\Domain\PlanTemplateRegistry;
use Sigma\PlannerEngine\Domain\Planner;

/**
 * O Module `planner-engine` — o quarto `IModule` de domínio do SIGMA.
 *
 * Diferente de `identity-engine`/`memory-engine`/`mission-engine`, não
 * abre conexão com banco nenhum e não roda migration: o Planner não
 * persiste nada (ADR-0095). Por isso `configSchema()` não exige
 * variável alguma, e `register()` não pode falhar por infraestrutura
 * indisponível — este Module está `Ready` assim que registrado.
 */
final class PlannerEngineModule implements IModule
{
    private const VERSION = '1.0.0';

    private bool $registered = false;

    /** @param array<string, string|null> $env Mantido por simetria com os demais Modules; este não lê nada do ambiente. */
    public function __construct(private readonly array $env = [])
    {
    }

    public function name(): string
    {
        return 'planner-engine';
    }

    public function kind(): ModuleKind
    {
        return ModuleKind::Engine;
    }

    public function dependsOn(): array
    {
        return ['kernel', 'event-bus'];
    }

    public function configSchema(): ConfigSchema
    {
        // Nenhuma variável obrigatória — sem banco, sem estado.
        return new ConfigSchema(required: [], optional: []);
    }

    public function register(IContainer $container): void
    {
        /** @var IEventBus $eventBus */
        $eventBus = $container->get(IEventBus::class);

        $planner = new Planner(new PlanTemplateRegistry());

        $container->bind(Planner::class, $planner);
        $container->bind(PlanFromIntent::class, new PlanFromIntent($planner, $eventBus));

        $this->registered = true;
    }

    public function boot(): void
    {
        // Nada a inicializar: sem migration, sem conexão, sem listener.
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name(),
            type: $this->kind(),
            version: self::VERSION,
            status: $this->registered ? ModuleStatus::Ready : ModuleStatus::Boot,
            capabilities: ['PlanFromIntent'],
            dependencies: $this->dependsOn(),
        );
    }
}
