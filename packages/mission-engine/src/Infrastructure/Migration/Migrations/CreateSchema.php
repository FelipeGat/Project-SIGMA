<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Infrastructure\Migration\Migrations;

use Sigma\MissionEngine\Infrastructure\Migration\Migration;

/**
 * Seis tabelas — cinco previstas na Proposal 5C mais
 * `subtask_retry_attempts`, achado real desta Implementation (ver
 * Decision Log): `RetryAttempt` é uma lista aninhada dentro de
 * `Subtask`, mesmo padrão de `mission_history`, e a Proposal não a
 * havia listado separadamente. `tenant_id` obrigatório só em
 * `missions` (ADR-0021) — as demais só existem dentro do limite do
 * aggregate que referenciam via FK, sem `tenant_id` próprio (mesmo
 * padrão de `sessions` no Identity Engine). Sem FOREIGN KEY para
 * `tenants`/`workspaces` do Identity Engine: são referências opacas de
 * outro bounded context (mesmo raciocínio já usado em
 * `memory_records`). `missions.pending_approval_gate_id` também não
 * tem FOREIGN KEY, por um motivo diferente: evitar uma dependência
 * circular entre `missions`/`approval_gates` (uma referencia a outra
 * e vice-versa) — a integridade dessa referência é garantida pela
 * ordem de escrita de `PdoMissionRepository::save()` (missions antes
 * de approval_gates), não pelo schema.
 */
final class CreateSchema implements Migration
{
    public function version(): string
    {
        return '0001_create_schema';
    }

    public function up(\PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS missions (
                id CHAR(36) NOT NULL PRIMARY KEY,
                tenant_id CHAR(36) NOT NULL,
                workspace_id CHAR(36) NULL,
                correlation_id CHAR(36) NOT NULL,
                intent_id CHAR(36) NULL,
                objective TEXT NOT NULL,
                plan_json JSON NOT NULL,
                actor_type VARCHAR(20) NOT NULL,
                actor_id VARCHAR(255) NOT NULL,
                autonomy_ceiling TINYINT NOT NULL,
                status VARCHAR(20) NOT NULL,
                pending_approval_gate_id CHAR(36) NULL,
                created_at DATETIME NOT NULL,
                started_at DATETIME NULL,
                finished_at DATETIME NULL,
                INDEX idx_missions_tenant (tenant_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS subtasks (
                id CHAR(36) NOT NULL PRIMARY KEY,
                mission_id CHAR(36) NOT NULL,
                description TEXT NOT NULL,
                candidate_agent VARCHAR(255) NULL,
                candidate_capability VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL,
                result_json JSON NULL,
                INDEX idx_subtasks_mission (mission_id),
                CONSTRAINT fk_subtasks_mission FOREIGN KEY (mission_id) REFERENCES missions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS subtask_retry_attempts (
                subtask_id CHAR(36) NOT NULL,
                attempt_number INT NOT NULL,
                reason TEXT NOT NULL,
                at DATETIME NOT NULL,
                PRIMARY KEY (subtask_id, attempt_number),
                CONSTRAINT fk_subtask_retry_attempts_subtask FOREIGN KEY (subtask_id) REFERENCES subtasks (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS approval_gates (
                id CHAR(36) NOT NULL PRIMARY KEY,
                mission_id CHAR(36) NOT NULL,
                reason TEXT NOT NULL,
                requested_at DATETIME NOT NULL,
                decision VARCHAR(20) NOT NULL,
                decided_at DATETIME NULL,
                decided_by VARCHAR(255) NULL,
                INDEX idx_approval_gates_mission (mission_id),
                CONSTRAINT fk_approval_gates_mission FOREIGN KEY (mission_id) REFERENCES missions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS compensations (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                mission_id CHAR(36) NOT NULL,
                subtask_id CHAR(36) NOT NULL,
                action TEXT NOT NULL,
                at DATETIME NOT NULL,
                result VARCHAR(30) NOT NULL,
                INDEX idx_compensations_mission (mission_id),
                CONSTRAINT fk_compensations_mission FOREIGN KEY (mission_id) REFERENCES missions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS mission_history (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                mission_id CHAR(36) NOT NULL,
                status VARCHAR(20) NOT NULL,
                at DATETIME NOT NULL,
                INDEX idx_mission_history_mission (mission_id),
                CONSTRAINT fk_mission_history_mission FOREIGN KEY (mission_id) REFERENCES missions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }
}
