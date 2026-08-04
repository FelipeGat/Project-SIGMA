<?php

declare(strict_types=1);

/**
 * Front controller mínimo da Release 2 — apenas os três endpoints de
 * health. Não é Laravel: a superfície desta Release é pequena demais
 * para justificar o framework completo (ver Decision Log da Release 2).
 * services/gateway ganha um framework HTTP quando tiver uma API de
 * domínio real para expor.
 */

require __DIR__ . '/../vendor/autoload.php';

use Sigma\Gateway\Bootstrap;
use Sigma\Core\Envelope;
use Sigma\Gateway\HealthEndpoints;

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';

// $_ENV depende de `variables_order` incluir "E" no php.ini — ausente
// por padrão em várias instalações (XAMPP inclusive), inclusive sob o
// servidor embutido do PHP. `getenv()` sem argumentos não sofre dessa
// limitação e é a forma confiável de ler o ambiente entre SAPIs.
$env = getenv() ?: [];

try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(Envelope::failure('bootstrap.failed', $exception->getMessage()), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

    exit;
}

$endpoints = new HealthEndpoints($bootstrap->health);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH) ?: '/';

[$status, $body] = match ($path) {
    '/health/live' => $endpoints->live(),
    '/health/ready' => $endpoints->ready(),
    '/health/startup' => $endpoints->startup(),
    default => [404, Envelope::failure('route.not_found', sprintf('Rota "%s" não existe nesta Release.', $path))],
};

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($body, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
