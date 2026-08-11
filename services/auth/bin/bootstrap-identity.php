<?php

declare(strict_types=1);

/**
 * Cria a hierarquia mínima operável do SIGMA — Tenant → Company →
 * Workspace → User — para que exista alguém capaz de logar.
 *
 * Antes desta Release não havia nenhum caminho de produção para isso:
 * `RegisterIdentity` exige um Tenant existente, e Tenant/Company/
 * Workspace só nasciam em fixtures de teste (achado da Release 5.5).
 *
 * Sem HTTP, mesmo padrão de services/memory-worker/bin/worker.php.
 *
 * Uso:
 *   php bin/bootstrap-identity.php \
 *     --tenant="Alfa Soluções" --company="Alfa Tecnologia" \
 *     --workspace="Operação" --name="Rossini Santos" \
 *     --email=rossini@alfa.com.br --password='trocar-depois'
 */

require __DIR__ . '/../vendor/autoload.php';

use Sigma\Auth\Bootstrap;
use Sigma\Auth\IdentityBootstrapper;

$options = getopt('', ['tenant:', 'company:', 'workspace:', 'name:', 'email:', 'password:']);

$required = ['tenant', 'company', 'workspace', 'name', 'email', 'password'];
$missing = array_values(array_filter($required, static fn (string $key): bool => !isset($options[$key]) || $options[$key] === ''));

if ($missing !== []) {
    fwrite(\STDERR, sprintf(
        "bootstrap-identity: faltam argumentos obrigatórios: %s\n\nUso:\n  php bin/bootstrap-identity.php --tenant=... --company=... --workspace=... --name=... --email=... --password=...\n",
        implode(', ', array_map(static fn (string $key): string => '--' . $key, $missing)),
    ));

    exit(1);
}

$manifestPath = getenv('SIGMA_MANIFEST_PATH') ?: __DIR__ . '/../../../system-manifest.yaml';
$env = getenv() ?: [];

try {
    $bootstrap = Bootstrap::fromManifestFile($manifestPath, $env);
} catch (\Throwable $exception) {
    fwrite(\STDERR, sprintf("bootstrap-identity: falha no boot — %s\n", $exception->getMessage()));

    exit(1);
}

try {
    $result = (new IdentityBootstrapper($bootstrap->container))->execute(
        (string) $options['tenant'],
        (string) $options['company'],
        (string) $options['workspace'],
        (string) $options['name'],
        (string) $options['email'],
        (string) $options['password'],
    );
} catch (\Throwable $exception) {
    fwrite(\STDERR, sprintf("bootstrap-identity: falha ao criar a hierarquia — %s\n", $exception->getMessage()));

    exit(1);
}

fwrite(\STDOUT, json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . "\n");
fwrite(\STDOUT, "\nGuarde tenantId e workspaceId — POST /auth/login e POST /auth/workspace precisam dos dois.\n");
