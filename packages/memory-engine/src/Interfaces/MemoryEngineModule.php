<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Interfaces;

use Sigma\Core\SigmaException;
use Sigma\Kernel\ConfigurationProvider;
use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\MemoryEngine\Application\UseCase\AppendContextMemoryContent;
use Sigma\MemoryEngine\Application\UseCase\CheckDigitalTwinStaleness;
use Sigma\MemoryEngine\Application\UseCase\CloseAndDistillContextMemory;
use Sigma\MemoryEngine\Application\UseCase\EvaluateMemoryPromotion;
use Sigma\MemoryEngine\Application\UseCase\IndexKnowledgeFromFile;
use Sigma\MemoryEngine\Application\UseCase\MarkMemoryContradicted;
use Sigma\MemoryEngine\Application\UseCase\PinMemorySubject;
use Sigma\MemoryEngine\Application\UseCase\ProjectDigitalTwinFromEvent;
use Sigma\MemoryEngine\Application\UseCase\ReactivateMemoryRecord;
use Sigma\MemoryEngine\Application\UseCase\RetractMemoryRecord;
use Sigma\MemoryEngine\Application\UseCase\ReviseKnowledgeFromFile;
use Sigma\MemoryEngine\Application\UseCase\StartContextMemory;
use Sigma\MemoryEngine\Application\UseCase\UnpinMemorySubject;
use Sigma\MemoryEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\MemoryEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoContextMemoryRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoDigitalTwinRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoKnowledgeRecordRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoMemoryRecordRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoPinnedMemorySubjectRepository;

/**
 * O Module `memory-engine` — segundo `IModule` de domínio real do
 * SIGMA, depois de `identity-engine` (`kind: Engine`, ADR-0040).
 * `register()` conecta ao banco, monta os casos de uso, e assina os
 * eventos do Identity Engine (`identity.created`/`workspace.selected`)
 * via `IEventBus::subscribe()` — a entrega cross-processo de fato
 * depende de `services/memory-worker` rodar `RedisSubscriber::listen()`
 * separadamente (ver Proposal 4B — Arquitetura). `boot()` aplica as
 * migrations, mesmo padrão de `IdentityEngineModule`.
 */
final class MemoryEngineModule implements IModule
{
    private const VERSION = '1.0.0';

    private ?\PDO $pdo = null;

    /** @param array<string, string|null> $env Fonte de configuração — getenv() por padrão, injetável para testes. */
    public function __construct(private readonly array $env = [])
    {
    }

    public function name(): string
    {
        return 'memory-engine';
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
        return new ConfigSchema(
            required: ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'],
            optional: ['DB_PASSWORD'],
        );
    }

    public function register(IContainer $container): void
    {
        $config = new ConfigurationProvider($this->configSchema(), $this->env, $this->name());

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config->require('DB_HOST'),
            $config->require('DB_PORT'),
            $config->require('DB_NAME'),
        );
        try {
            $this->pdo = new \PDO($dsn, $config->require('DB_USER'), (string) $config->get('DB_PASSWORD', ''), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $exception) {
            throw new SigmaException(
                sprintf('Module "memory-engine": não foi possível conectar ao banco — %s', $exception->getMessage()),
                'memory_engine.database_unreachable',
                $exception,
            );
        }

        $contextMemories = new PdoContextMemoryRepository($this->pdo);
        $memoryRecords = new PdoMemoryRecordRepository($this->pdo);
        $knowledgeRecords = new PdoKnowledgeRecordRepository($this->pdo);
        $digitalTwins = new PdoDigitalTwinRepository($this->pdo);
        $pinnedSubjects = new PdoPinnedMemorySubjectRepository($this->pdo);

        /** @var IEventBus $eventBus */
        $eventBus = $container->get(IEventBus::class);

        $container->bind(StartContextMemory::class, new StartContextMemory($contextMemories, $eventBus));
        $container->bind(AppendContextMemoryContent::class, new AppendContextMemoryContent($contextMemories));
        $container->bind(CloseAndDistillContextMemory::class, new CloseAndDistillContextMemory($contextMemories, $memoryRecords, $eventBus));
        $container->bind(EvaluateMemoryPromotion::class, new EvaluateMemoryPromotion($memoryRecords, $eventBus));
        $container->bind(MarkMemoryContradicted::class, new MarkMemoryContradicted($memoryRecords, $eventBus));
        $container->bind(ReactivateMemoryRecord::class, new ReactivateMemoryRecord($memoryRecords, $eventBus));
        $container->bind(RetractMemoryRecord::class, new RetractMemoryRecord($memoryRecords, $eventBus));
        $container->bind(PinMemorySubject::class, new PinMemorySubject($pinnedSubjects, $eventBus));
        $container->bind(UnpinMemorySubject::class, new UnpinMemorySubject($pinnedSubjects));
        $container->bind(IndexKnowledgeFromFile::class, new IndexKnowledgeFromFile($knowledgeRecords, $eventBus));
        $container->bind(ReviseKnowledgeFromFile::class, new ReviseKnowledgeFromFile($knowledgeRecords, $eventBus));
        $container->bind(CheckDigitalTwinStaleness::class, new CheckDigitalTwinStaleness($digitalTwins, $eventBus));

        $projectTwin = new ProjectDigitalTwinFromEvent($digitalTwins, $eventBus);
        $container->bind(ProjectDigitalTwinFromEvent::class, $projectTwin);

        // A entrega cross-processo de fato (Redis → este handler) exige
        // que este processo esteja rodando RedisSubscriber::listen() —
        // registrar aqui só garante que, quando a mensagem chegar (via
        // RedisEventBus::dispatchLocally(), dentro do mesmo processo),
        // o handler certo é chamado. Ver services/memory-worker.
        $eventBus->subscribe('identity.created', fn (array $payload) => $projectTwin->handle('identity.created', $payload, new \DateTimeImmutable()));
        $eventBus->subscribe('workspace.selected', fn (array $payload) => $projectTwin->handle('workspace.selected', $payload, new \DateTimeImmutable()));
    }

    public function boot(): void
    {
        (new MigrationRunner($this->pdo))->run([new CreateSchema()]);
    }

    public function describe(): ComponentDescriptor
    {
        return new ComponentDescriptor(
            id: $this->name(),
            type: $this->kind(),
            version: self::VERSION,
            status: $this->pdo !== null ? ModuleStatus::Ready : ModuleStatus::Boot,
            capabilities: [
                'StartContextMemory', 'CloseAndDistillContextMemory', 'EvaluateMemoryPromotion',
                'MarkMemoryContradicted', 'ReactivateMemoryRecord', 'RetractMemoryRecord',
                'PinMemorySubject', 'UnpinMemorySubject', 'IndexKnowledgeFromFile',
                'ReviseKnowledgeFromFile', 'ProjectDigitalTwinFromEvent', 'CheckDigitalTwinStaleness',
            ],
            dependencies: $this->dependsOn(),
        );
    }
}
