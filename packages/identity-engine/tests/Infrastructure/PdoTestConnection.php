<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Infrastructure;

use Sigma\IdentityEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\IdentityEngine\Infrastructure\Migration\Migrations\CreateSchema;

/**
 * Conexão real com MariaDB para os testes de Infrastructure — lê de
 * variáveis de ambiente (mesmas usadas pelo Module em produção), com
 * defaults que apontam para o `mariadb` do docker-compose. Se nenhuma
 * MariaDB estiver alcançável, os testes desta pasta são pulados
 * explicitamente (`markTestSkipped`), nunca falham por engano.
 */
final class PdoTestConnection
{
    public static function connectOrSkip(): \PDO
    {
        $host = getenv('IDENTITY_TEST_DB_HOST') ?: '127.0.0.1';
        $port = getenv('IDENTITY_TEST_DB_PORT') ?: '3306';
        $name = getenv('IDENTITY_TEST_DB_NAME') ?: 'sigma_identity_test';
        $user = getenv('IDENTITY_TEST_DB_USER') ?: 'root';
        $password = getenv('IDENTITY_TEST_DB_PASSWORD') ?: '';

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
            );
        } catch (\PDOException $exception) {
            \PHPUnit\Framework\Assert::markTestSkipped('MariaDB não alcançável para testes de Infrastructure: ' . $exception->getMessage());
        }

        self::resetSchema($pdo);
        (new MigrationRunner($pdo))->run([new CreateSchema()]);

        return $pdo;
    }

    private static function resetSchema(\PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
