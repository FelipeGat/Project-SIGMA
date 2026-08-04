<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Infrastructure;

use Sigma\MemoryEngine\Infrastructure\Migration\MigrationRunner;
use Sigma\MemoryEngine\Infrastructure\Migration\Migrations\CreateSchema;

/**
 * Conexão real com MariaDB para os testes de Infrastructure — mesmo
 * padrão de packages/identity-engine/tests/Infrastructure/PdoTestConnection.php.
 * Usa um banco de teste próprio (`sigma_memory_test`), distinto do de
 * identity-engine (`sigma_identity_test`) mesmo os dois reaproveitando
 * a mesma MariaDB em produção (`sigma_identity`) — o reset de schema
 * daqui apaga todas as tabelas do banco apontado, isolar evita que os
 * dois conjuntos de teste se atropelem.
 */
final class PdoTestConnection
{
    public static function connectOrSkip(): \PDO
    {
        $host = getenv('MEMORY_TEST_DB_HOST') ?: '127.0.0.1';
        $port = getenv('MEMORY_TEST_DB_PORT') ?: '3306';
        $name = getenv('MEMORY_TEST_DB_NAME') ?: 'sigma_memory_test';
        $user = getenv('MEMORY_TEST_DB_USER') ?: 'root';
        $password = getenv('MEMORY_TEST_DB_PASSWORD') ?: '';

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
