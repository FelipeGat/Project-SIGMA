<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Infrastructure;

use Sigma\MissionEngine\Infrastructure\Migration\Migrations\CreateSchema;
use Sigma\MissionEngine\Infrastructure\Migration\MigrationRunner;

/**
 * Conexão real com MariaDB para os testes de Infrastructure — mesmo
 * padrão de identity-engine/memory-engine. Banco de teste próprio
 * (`sigma_mission_test`), distinto dos outros dois mesmo reaproveitando
 * a mesma MariaDB em produção.
 */
final class PdoTestConnection
{
    public static function connectOrSkip(): \PDO
    {
        $host = getenv('MISSION_TEST_DB_HOST') ?: '127.0.0.1';
        $port = getenv('MISSION_TEST_DB_PORT') ?: '3306';
        $name = getenv('MISSION_TEST_DB_NAME') ?: 'sigma_mission_test';
        $user = getenv('MISSION_TEST_DB_USER') ?: 'root';
        $password = getenv('MISSION_TEST_DB_PASSWORD') ?: '';

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
