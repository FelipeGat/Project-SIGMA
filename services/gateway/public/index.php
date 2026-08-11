<?php

declare(strict_types=1);

/**
 * Front controller mínimo — os três endpoints de health (Release 2) e
 * as rotas de Mission (Release 5.5) e POST /intents (6B). Continua sem
 * Laravel: seis rotas ainda não justificam o framework completo (ver Decision Log da
 * Release 2). services/gateway ganha um framework HTTP quando expuser
 * o ciclo de vida completo da Mission — escopo da Release 12.
 */

require __DIR__ . '/../vendor/autoload.php';

use Sigma\Kernel\Http\BootFailureEndpoints;
use Sigma\Gateway\Bootstrap;
use Sigma\Core\Envelope;
use Sigma\Kernel\Http\HealthEndpoints;
use Sigma\Gateway\IntentEndpoints;
use Sigma\Gateway\MissionEndpoints;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';

// $_ENV depende de `variables_order` incluir "E" no php.ini — ausente
// por padrão em várias instalações (XAMPP inclusive), inclusive sob o
// servidor embutido do PHP. `getenv()` sem argumentos não sofre dessa
// limitação e é a forma confiável de ler o ambiente entre SAPIs.
$env = getenv() ?: [];

// A partir da Release 5.5 o gateway depende de MariaDB (identity-engine
// e mission-engine), então uma falha de boot deixou de significar
// "configuração quebrada" e passou a poder significar "banco piscou".
// Por isso o boot falho não responde mais 503 a tudo: `/health/live`
// continua 200 (o processo está vivo; reiniciar não resolveria), e só
// `ready`/`startup` e as rotas de domínio reportam indisponibilidade.
// Ver BootFailureEndpoints e BOOTSTRAP.md § Health.
$bootstrap = null;
$bootFailure = null;
try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    error_log(sprintf('[gateway] falha no boot: %s', $exception->getMessage()));
    $bootFailure = new BootFailureEndpoints($exception);
}

$health = $bootstrap !== null ? new HealthEndpoints($bootstrap->health) : $bootFailure;
$missions = $bootstrap !== null ? new MissionEndpoints($bootstrap->container) : null;
$intents = $bootstrap !== null ? new IntentEndpoints($bootstrap->container) : null;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$sessionToken = str_starts_with($authorizationHeader, 'Bearer ') ? substr($authorizationHeader, 7) : null;

$requestBody = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input') ?: '{}';
    $requestBody = json_decode($raw, true) ?? [];
}

// `GET /missions/{id}` é a primeira rota do SIGMA com segmento
// variável — daí o preg_match, em vez de um match sobre o path exato.
$missionIdMatch = [];
$isMissionDetail = $method === 'GET' && preg_match('#^/missions/([^/]+)$#', $path, $missionIdMatch) === 1;

[$status, $body] = match (true) {
    $method === 'GET' && $path === '/health/live' => $health->live(),
    $method === 'GET' && $path === '/health/ready' => $health->ready(),
    $method === 'GET' && $path === '/health/startup' => $health->startup(),
    $method === 'POST' && $path === '/missions' => $missions?->create($sessionToken, $requestBody) ?? $bootFailure->unavailable(),
    $method === 'POST' && $path === '/intents' => $intents?->create($sessionToken, $requestBody) ?? $bootFailure->unavailable(),
    $isMissionDetail => $missions?->get($sessionToken, urldecode($missionIdMatch[1])) ?? $bootFailure->unavailable(),
    default => [404, Envelope::failure('route.not_found', sprintf('Rota "%s %s" não existe nesta Release.', $method, $path))],
};

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($body, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
