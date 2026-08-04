<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Migration;

/**
 * Runner mínimo — aplica cada Migration não aplicada ainda, em ordem,
 * registrando o que já rodou numa tabela própria. Sem rollback: uma
 * migration incorreta é corrigida por uma nova migration, nunca por
 * desfazer a anterior (mesmo princípio de "eventos não são reescritos"
 * já usado em EVENT_MODEL.md, aplicado aqui a schema).
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
            'CREATE TABLE IF NOT EXISTS identity_engine_migrations (
                version VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB',
        );
    }

    /** @return list<string> */
    private function appliedVersions(): array
    {
        $statement = $this->pdo->query('SELECT version FROM identity_engine_migrations');

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function markApplied(string $version): void
    {
        $statement = $this->pdo->prepare('INSERT INTO identity_engine_migrations (version, applied_at) VALUES (?, NOW())');
        $statement->execute([$version]);
    }
}
