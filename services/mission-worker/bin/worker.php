<?php

declare(strict_types=1);

/**
 * Entrypoint do services/mission-worker — sem HTTP.
 *
 * Fecha o ciclo que a Release 6B abriu: o Planner publica
 * `mission.planned`, este processo consome e cria a Mission. É o
 * primeiro consumidor real de evento do Mission Engine — até a Release
 * 5C ele apenas publicava.
 *
 * Mesmo padrão de services/memory-worker/bin/worker.php (Release 4B),
 * inclusive o segundo Predis\Client dedicado: `pubSubLoop()` ocupa a
 * conexão inteiramente, por isso nunca reaproveita o client que
 * EventBusModule já criou para publish().
 *
 * LIMITAÇÃO CONHECIDA, não contornada aqui: Redis pub/sub não guarda
 * mensagem para assinante ausente (confirmado com evidência real na
 * Release 4.5). Uma Intent planejada enquanto este processo estiver
 * fora do ar NÃO vira Mission, e ninguém percebe — o Planner é sem
 * estado e quem chamou já recebeu 202. `restart: on-failure` reduz a
 * janela; Redis Streams resolveria, e é decisão de outra Release.
 */

require __DIR__ . '/../vendor/autoload.php';

use Predis\Client;
use Sigma\EventBus\RedisEventBus;
use Sigma\EventBus\RedisSubscriber;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MissionEngine\Application\UseCase\CreateMissionFromPlannedEvent;
use Sigma\MissionWorker\Bootstrap;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';
$env = getenv() ?: [];

try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    fwrite(\STDERR, sprintf("mission-worker: falha no boot — %s\n", $exception->getMessage()));

    exit(1);
}

$eventBus = $bootstrap->container->get(IEventBus::class);
if (!$eventBus instanceof RedisEventBus) {
    fwrite(\STDERR, "mission-worker: IEventBus não é RedisEventBus — este worker exige entrega cross-processo real.\n");

    exit(1);
}

/** @var CreateMissionFromPlannedEvent $createFromEvent */
$createFromEvent = $bootstrap->container->get(CreateMissionFromPlannedEvent::class);

// O handler é registrado aqui, explicitamente, e não dentro de
// MissionEngineModule: o Engine não deve saber que existe um processo
// consumindo seus eventos — quem faz essa amarração é o service, na
// borda. Mesmo raciocínio que mantém o Planner sem conhecer o Mission.
$eventBus->subscribe('mission.planned', static function (array $payload) use ($createFromEvent): void {
    try {
        $mission = $createFromEvent->execute($payload, new \DateTimeImmutable());
        fwrite(\STDOUT, sprintf(
            "mission-worker: Mission %s criada a partir de mission.planned (correlationId %s).\n",
            $mission->id()->toString(),
            $payload['correlationId'] ?? '?',
        ));
    } catch (\Throwable $exception) {
        // Um payload inválido não pode derrubar o loop: o worker
        // precisa continuar consumindo os próximos eventos. A mensagem
        // problemática é perdida — registrada aqui, sem retry nem
        // dead-letter, mesma limitação já aceita no memory-worker.
        fwrite(\STDERR, sprintf(
            "mission-worker: falha ao processar mission.planned (correlationId %s) — %s\n",
            $payload['correlationId'] ?? '?',
            $exception->getMessage(),
        ));
    }
});

$subscriberClient = new Client([
    'host' => $env['REDIS_HOST'] ?? 'redis',
    'port' => (int) ($env['REDIS_PORT'] ?? 6379),
    'password' => $env['REDIS_PASSWORD'] ?? null,
    // Sem isto, o socket usa o timeout padrão do PHP (~60s) e o
    // processo morre assim que fica um tempo sem receber mensagem —
    // achado real da Release 4B, ver o Decision Log de lá.
    'read_write_timeout' => -1,
]);

fwrite(\STDOUT, "mission-worker: pronto, aguardando mission.planned.\n");

(new RedisSubscriber($subscriberClient))->listen(
    ['mission.planned'],
    static function (string $event, array $payload) use ($eventBus): void {
        $eventBus->dispatchLocally($event, $payload);
    },
);
