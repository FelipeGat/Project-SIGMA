<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * A única unidade que o Bootstrap conhece. Engine, Plugin, Service e
 * Package implementam este mesmo contrato — `kind()` é metadado, sem
 * efeito no comportamento do Kernel (ver ADR-0040).
 */
interface IModule
{
    public function name(): string;

    public function kind(): ModuleKind;

    /**
     * @return string[] Nomes de outros Modules que precisam estar registrados antes deste.
     */
    public function dependsOn(): array;

    public function configSchema(): ConfigSchema;

    /**
     * Registra bindings no Container — nunca executa nada além disso.
     */
    public function register(IContainer $container): void;

    /**
     * Inicialização de fato — roda somente depois que todos os Modules
     * já tiveram `register()` chamado.
     */
    public function boot(): void;

    public function describe(): ComponentDescriptor;
}
