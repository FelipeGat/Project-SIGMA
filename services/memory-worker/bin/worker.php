<?php

declare(strict_types=1);

/**
 * Entrypoint do services/memory-worker — sem HTTP (ver Proposal 4B:
 * nenhuma API pública nesta Release). Sobe o Kernel/MemoryEngineModule
 * normalmente (mesmo padrão de services/auth/public/index.php), depois
 * entra num loop bloqueante consumindo identity.created/workspace.selected
 * do Redis via um segundo Predis\Client dedicado — pubSubLoop() ocupa a
 * conexão inteiramente, por isso nunca reaproveita o client que
 * EventBusModule já criou para publish() (ver Decision Log da 4B).
 */

require __DIR__ . '/../vendor/autoload.php';

use Predis\Client;
use Sigma\EventBus\RedisEventBus;
use Sigma\EventBus\RedisSubscriber;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryWorker\Bootstrap;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';
$env = getenv() ?: [];

try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    fwrite(\STDERR, sprintf("memory-worker: falha no boot — %s\n", $exception->getMessage()));

    exit(1);
}

$eventBus = $bootstrap->container->get(IEventBus::class);
if (!$eventBus instanceof RedisEventBus) {
    fwrite(\STDERR, "memory-worker: IEventBus não é RedisEventBus — este worker exige entrega cross-processo real.\n");

    exit(1);
}

$subscriberClient = new Client([
    'host' => $env['REDIS_HOST'] ?? 'redis',
    'port' => (int) ($env['REDIS_PORT'] ?? 6379),
    'password' => $env['REDIS_PASSWORD'] ?? null,
    // Sem isto, o socket usa o timeout padrão do PHP (~60s) e o processo
    // morre com ConnectionException assim que fica um tempo sem receber
    // nenhuma mensagem — achado real ao validar via Docker (ver Decision
    // Log da 4B). -1 desativa o timeout de leitura/escrita no Predis.
    'read_write_timeout' => -1,
]);

fwrite(\STDOUT, "memory-worker: pronto, aguardando identity.created/workspace.selected.\n");

(new RedisSubscriber($subscriberClient))->listen(
    ['identity.created', 'workspace.selected'],
    static function (string $event, array $payload) use ($eventBus): void {
        $eventBus->dispatchLocally($event, $payload);
    },
);
