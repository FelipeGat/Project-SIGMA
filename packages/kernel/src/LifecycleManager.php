<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\ILogger;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\Kernel\Manifest\ModuleReference;
use Sigma\Kernel\Manifest\SystemManifest;

/**
 * Orquestra `register → boot → start → ready` sobre os Modules
 * declarados no System Manifest, na ordem topológica de `dependsOn()`.
 * `discover` (ler o Manifest) acontece antes, via SystemManifestLoader;
 * `degraded`/`shutdown` são tratados fora do boot inicial — ver
 * BOOTSTRAP.md e ADR-0041.
 */
final class LifecycleManager
{
    /** @var array<string, IModule> */
    private array $registry = [];

    /** @var string[] Ordem em que os Modules desta execução foram inicializados — usada pelo shutdown. */
    private array $bootOrder = [];

    public function __construct(
        private readonly IContainer $container,
        private readonly HealthManager $health,
        private readonly ILogger $logger,
    ) {
    }

    /**
     * Torna um Module conhecido do Lifecycle Manager — precede `boot()`.
     * Não é "discover" (que lê o Manifest); é o registro de quais
     * implementações concretas existem para os nomes que o Manifest
     * pode referenciar.
     */
    public function registerModule(IModule $module): void
    {
        $this->registry[$module->name()] = $module;
    }

    /**
     * Executa `register → boot → start → ready` para os Modules que o
     * Manifest declara. Lança SigmaException e não deixa nenhum Module
     * chegar a `ready` se: um Module obrigatório não está registrado,
     * uma versão é incompatível, ou há dependência circular.
     */
    public function boot(SystemManifest $manifest): void
    {
        $toLoad = $this->resolveModulesToLoad($manifest);
        $order = $this->resolveBootOrder($toLoad);

        foreach ($order as $name) {
            $toLoad[$name]->register($this->container);
        }

        foreach ($order as $name) {
            $this->bootModule($name, $toLoad[$name]);
            $this->bootOrder[] = $name;
        }

        $this->health->markStartupComplete();
    }

    /**
     * Encerra os Modules já inicializados nesta execução, na ordem
     * inversa de dependência, drenando trabalho antes de fechar
     * conexões — cada Module decide o que "drenar" significa para si.
     */
    public function shutdown(): void
    {
        foreach (array_reverse($this->bootOrder) as $name) {
            $this->health->report($name, ModuleStatus::Shutdown);
            $this->logger->info('Module encerrado.', ['module' => $name]);
        }

        $this->bootOrder = [];
    }

    /**
     * @return array<string, IModule>
     */
    private function resolveModulesToLoad(SystemManifest $manifest): array
    {
        $toLoad = [];

        foreach ($manifest->modules as $ref) {
            $module = $this->registry[$ref->name] ?? null;

            if ($module === null) {
                if ($ref->optional) {
                    $this->logger->warning('Módulo opcional ausente — ignorado.', ['module' => $ref->name]);
                    continue;
                }

                throw new SigmaException(
                    sprintf('Módulo obrigatório "%s" declarado no System Manifest não está registrado.', $ref->name),
                    'lifecycle.required_module_missing',
                );
            }

            $this->assertCompatible($module, $ref);
            $toLoad[$ref->name] = $module;
        }

        return $toLoad;
    }

    private function assertCompatible(IModule $module, ModuleReference $ref): void
    {
        if ($ref->minVersion === null) {
            return;
        }

        $actual = $module->describe()->version;

        if (version_compare($actual, $ref->minVersion, '<')) {
            throw new SigmaException(
                sprintf(
                    'Módulo "%s" versão %s é incompatível — mínimo exigido pelo Manifest: %s. Ver COMPATIBILITY.md.',
                    $ref->name,
                    $actual,
                    $ref->minVersion,
                ),
                'lifecycle.incompatible_module_version',
            );
        }
    }

    private function bootModule(string $name, IModule $module): void
    {
        $this->health->report($name, ModuleStatus::Boot);

        try {
            $module->boot();
        } catch (\Throwable $exception) {
            $this->health->report($name, ModuleStatus::Failed, $exception->getMessage());

            throw new SigmaException(
                sprintf('Module "%s" falhou ao inicializar: %s', $name, $exception->getMessage()),
                'lifecycle.module_boot_failed',
                $exception,
            );
        }

        $this->health->report($name, ModuleStatus::Start);
        $this->health->report($name, ModuleStatus::Ready);
    }

    /**
     * Ordenação topológica (DFS) restrita ao conjunto de Modules que
     * serão de fato carregados nesta execução — uma dependência para
     * fora desse conjunto não é seguida nem valida nesta Release.
     *
     * @param array<string, IModule> $toLoad
     * @return string[]
     */
    private function resolveBootOrder(array $toLoad): array
    {
        $order = [];
        $visited = [];
        $visiting = [];

        foreach (array_keys($toLoad) as $name) {
            $this->visit($name, $toLoad, $visited, $visiting, $order);
        }

        return $order;
    }

    /**
     * @param array<string, IModule> $toLoad
     * @param array<string, true> $visited
     * @param array<string, true> $visiting
     * @param string[] $order
     */
    private function visit(string $name, array $toLoad, array &$visited, array &$visiting, array &$order): void
    {
        if (isset($visited[$name])) {
            return;
        }

        if (isset($visiting[$name])) {
            throw new SigmaException(
                sprintf('Dependência circular detectada envolvendo o Module "%s".', $name),
                'lifecycle.circular_dependency',
            );
        }

        $visiting[$name] = true;

        foreach ($toLoad[$name]->dependsOn() as $dependency) {
            if (isset($toLoad[$dependency])) {
                $this->visit($dependency, $toLoad, $visited, $visiting, $order);
            }
        }

        unset($visiting[$name]);
        $visited[$name] = true;
        $order[] = $name;
    }
}
