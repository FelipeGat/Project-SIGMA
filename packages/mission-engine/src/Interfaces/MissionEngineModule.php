<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Interfaces;

use Sigma\Core\SigmaException;
use Sigma\Kernel\ConfigurationProvider;
use Sigma\Kernel\Contract\ComponentDescriptor;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\Contract\IContainer;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\Kernel\Contract\IModule;
use Sigma\Kernel\Contract\ModuleKind;
use Sigma\Kernel\Contract\ModuleStatus;
use Sigma\MissionEngine\Application\UseCase\AdvanceMissionToNextSubtask;
use Sigma\MissionEngine\Application\UseCase\ApproveMission;
use Sigma\MissionEngine\Application\UseCase\AssignSubtask;
use Sigma\MissionEngine\Application\UseCase\BeginMissionValidation;
use Sigma\MissionEngine\Application\UseCase\CancelMission;
use Sigma\MissionEngine\Application\UseCase\CompensateSubtask;
use Sigma\MissionEngine\Application\UseCase\CreateMission;
use Sigma\MissionEngine\Application\UseCase\FailMissionValidation;
use Sigma\MissionEngine\Application\UseCase\FailSubtask;
use Sigma\MissionEngine\Application\UseCase\GetMission;
use Sigma\MissionEngine\Application\UseCase\PassMissionValidation;
use Sigma\MissionEngine\Application\UseCase\RejectMission;
use Sigma\MissionEngine\Application\UseCase\RetrySubtask;
use Sigma\MissionEngine\Application\UseCase\StartSubtaskExecution;
use Sigma\MissionEngine\Application\UseCase\ValidateSubtask;
use Sigma\MissionEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\MissionEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\MissionEngine\Infrastructure\Pdo\PdoMissionRepository;

/**
 * O Module `mission-engine` — terceiro `IModule` de domínio real do
 * SIGMA, depois de `identity-engine`/`memory-engine` (`kind: Engine`).
 * Diferente de `MemoryEngineModule`, não assina nenhum evento — nenhum
 * Engine anterior publica evento que o Mission Engine precise
 * consumir nesta Release (ver Proposal 5C, "Diferença em relação à
 * Release 4B"). `boot()` aplica as migrations, mesmo padrão dos
 * outros dois Engines.
 */
final class MissionEngineModule implements IModule
{
    private const VERSION = '1.0.0';

    private ?\PDO $pdo = null;

    /** @param array<string, string|null> $env Fonte de configuração — getenv() por padrão, injetável para testes. */
    public function __construct(private readonly array $env = [])
    {
    }

    public function name(): string
    {
        return 'mission-engine';
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
                sprintf('Module "mission-engine": não foi possível conectar ao banco — %s', $exception->getMessage()),
                'mission_engine.database_unreachable',
                $exception,
            );
        }

        $missions = new PdoMissionRepository($this->pdo);

        /** @var IEventBus $eventBus */
        $eventBus = $container->get(IEventBus::class);

        $container->bind(CreateMission::class, new CreateMission($missions, $eventBus));
        $container->bind(AdvanceMissionToNextSubtask::class, new AdvanceMissionToNextSubtask($missions, $eventBus));
        $container->bind(ApproveMission::class, new ApproveMission($missions, $eventBus));
        $container->bind(RejectMission::class, new RejectMission($missions, $eventBus));
        $container->bind(AssignSubtask::class, new AssignSubtask($missions, $eventBus));
        $container->bind(StartSubtaskExecution::class, new StartSubtaskExecution($missions, $eventBus));
        $container->bind(RetrySubtask::class, new RetrySubtask($missions, $eventBus));
        $container->bind(ValidateSubtask::class, new ValidateSubtask($missions, $eventBus));
        $container->bind(FailSubtask::class, new FailSubtask($missions, $eventBus));
        $container->bind(CompensateSubtask::class, new CompensateSubtask($missions, $eventBus));
        $container->bind(BeginMissionValidation::class, new BeginMissionValidation($missions));
        $container->bind(PassMissionValidation::class, new PassMissionValidation($missions, $eventBus));
        $container->bind(FailMissionValidation::class, new FailMissionValidation($missions, $eventBus));
        $container->bind(CancelMission::class, new CancelMission($missions, $eventBus));
        $container->bind(GetMission::class, new GetMission($missions));
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
                'CreateMission', 'AdvanceMissionToNextSubtask', 'ApproveMission', 'RejectMission',
                'AssignSubtask', 'StartSubtaskExecution', 'RetrySubtask', 'ValidateSubtask',
                'FailSubtask', 'CompensateSubtask', 'BeginMissionValidation', 'PassMissionValidation',
                'FailMissionValidation', 'CancelMission', 'GetMission',
            ],
            dependencies: $this->dependsOn(),
        );
    }
}
