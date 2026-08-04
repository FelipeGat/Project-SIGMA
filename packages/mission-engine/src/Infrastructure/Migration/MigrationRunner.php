<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Infrastructure\Migration;

/**
 * Runner mínimo — mesmo de packages/identity-engine e
 * packages/memory-engine. Tabela de controle própria
 * (`mission_engine_migrations`), independente das dos outros Engines
 * mesmo reaproveitando a mesma MariaDB.
 */
final class MigrationRunner
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    /** @param list<Migration> $migrations */
    public function run(array $migrations): void
    {
        $this->ensureMigrationsTableExists();
        $applied = $this->appliedVersions();

        foreach ($migrations as $migration) {
            if (in_array($migration->version(), $applied, true)) {
                continue;
            }

            $migration->up($this->pdo);
            $this->markApplied($migration->version());
        }
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mission_engine_migrations (
                version VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
        );
    }

    /** @return list<string> */
    private function appliedVersions(): array
    {
        $statement = $this->pdo->query('SELECT version FROM mission_engine_migrations');

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function markApplied(string $version): void
    {
        $statement = $this->pdo->prepare('INSERT INTO mission_engine_migrations (version, applied_at) VALUES (?, NOW())');
        $statement->execute([$version]);
    }
}
