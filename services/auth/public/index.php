<?php

declare(strict_types=1);

/**
 * Front controller mínimo do services/auth — mesmo padrão de
 * services/gateway/public/index.php (Release 2): sem Laravel, a
 * superfície desta Release (quatro endpoints) não justifica o
 * framework completo ainda.
 */

require __DIR__ . '/../vendor/autoload.php';

use Sigma\Auth\AuthEndpoints;
use Sigma\Auth\Bootstrap;
use Sigma\Core\Envelope;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';

// Ver Decision Log da Release 2: getenv() sem argumentos, não $_ENV,
// é a forma confiável de ler o ambiente sob o servidor embutido do PHP.
$env = getenv() ?: [];

try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(Envelope::failure('bootstrap.failed', $exception->getMessage()), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

    exit;
}

$endpoints = new AuthEndpoints($bootstrap->container);

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
    $method === 'POST' && $path === '/auth/login' => $endpoints->login($body),
    $method === 'POST' && $path === '/auth/logout' => $endpoints->logout($sessionToken),
    $method === 'POST' && $path === '/auth/workspace' => $endpoints->selectWorkspace($sessionToken, $body),
    $method === 'GET' && $path === '/auth/context' => $endpoints->context($sessionToken),
    default => [404, Envelope::failure('route.not_found', sprintf('Rota "%s %s" não existe nesta Release.', $method, $path))],
};

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($responseBody, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
