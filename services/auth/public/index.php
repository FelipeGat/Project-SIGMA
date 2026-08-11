<?php

declare(strict_types=1);

/**
 * Front controller mínimo do services/auth — mesmo padrão de
 * services/gateway/public/index.php: sem Laravel, a superfície deste
 * service (quatro rotas de domínio, mais os três endpoints de health)
 * não justifica o framework completo ainda.
 *
 * Os três endpoints de health chegaram na Release 5.6 (ADR-0094).
 * Até então este service estava no ar desde a Release 3B sem nenhum
 * deles — um ponto cego operacional completo, apesar de ADR-0042 os
 * definir como padrão de todo processo deployável.
 */

require __DIR__ . '/../vendor/autoload.php';

use Sigma\Auth\AuthEndpoints;
use Sigma\Auth\Bootstrap;
use Sigma\Core\Envelope;
use Sigma\Kernel\Http\BootFailureEndpoints;
use Sigma\Kernel\Http\HealthEndpoints;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';

// Ver Decision Log da Release 2: getenv() sem argumentos, não $_ENV,
// é a forma confiável de ler o ambiente sob o servidor embutido do PHP.
$env = getenv() ?: [];

// Uma falha de boot aqui quase sempre significa "MariaDB piscou", não
// "configuração quebrada" — este service depende do banco desde a
// Release 3B. Por isso `/health/live` continua 200: o processo está
// vivo, e reiniciá-lo não traz o banco de volta. Ver ADR-0094 e
// BOOTSTRAP.md § Health.
$bootstrap = null;
$bootFailure = null;
try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    error_log(sprintf('[auth] falha no boot: %s', $exception->getMessage()));
    $bootFailure = new BootFailureEndpoints($exception);
}

$health = $bootstrap !== null ? new HealthEndpoints($bootstrap->health) : $bootFailure;
$endpoints = $bootstrap !== null ? new AuthEndpoints($bootstrap->container) : null;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$sessionToken = str_starts_with($authorizationHeader, 'Bearer ') ? substr($authorizationHeader, 7) : null;

$body = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input') ?: '{}';
    $body = json_decode($raw, true) ?? [];
}

[$status, $responseBody] = match (true) {
    $method === 'GET' && $path === '/health/live' => $health->live(),
    $method === 'GET' && $path === '/health/ready' => $health->ready(),
    $method === 'GET' && $path === '/health/startup' => $health->startup(),
    $method === 'POST' && $path === '/auth/login' => $endpoints?->login($body) ?? $bootFailure->unavailable(),
    $method === 'POST' && $path === '/auth/logout' => $endpoints?->logout($sessionToken) ?? $bootFailure->unavailable(),
    $method === 'POST' && $path === '/auth/workspace' => $endpoints?->selectWorkspace($sessionToken, $body) ?? $bootFailure->unavailable(),
    $method === 'GET' && $path === '/auth/context' => $endpoints?->context($sessionToken) ?? $bootFailure->unavailable(),
    default => [404, Envelope::failure('route.not_found', sprintf('Rota "%s %s" não existe nesta Release.', $method, $path))],
};

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($responseBody, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
