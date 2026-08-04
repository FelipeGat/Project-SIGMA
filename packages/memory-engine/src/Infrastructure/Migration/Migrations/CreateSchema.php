<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Migration\Migrations;

use Sigma\MemoryEngine\Infrastructure\Migration\Migration;

/**
 * As cinco tabelas de MEMORY_MODEL.md — `tenant_id` obrigatório em
 * todas (ADR-0021). Sem FOREIGN KEY para `tenants`/`workspaces`/
 * `identities` do Identity Engine: são referências opacas de outro
 * bounded context (ver Decision Log da Release 4A — TenantId/
 * WorkspaceId/MissionId são cópias locais), não linhas que este schema
 * possa validar por FK.
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
            CREATE TABLE IF NOT EXISTS context_memories (
                id CHAR(36) NOT NULL PRIMARY KEY,
                tenant_id CHAR(36) NOT NULL,
                workspace_id CHAR(36) NOT NULL,
                mission_id CHAR(36) NULL,
                origin VARCHAR(255) NOT NULL,
                raw_content LONGTEXT NOT NULL,
                started_at DATETIME NOT NULL,
                ended_at DATETIME NULL,
                status VARCHAR(20) NOT NULL,
                INDEX idx_context_memories_tenant (tenant_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS memory_records (
                id CHAR(36) NOT NULL PRIMARY KEY,
                level VARCHAR(20) NOT NULL,
                subject_key VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                confidence DECIMAL(3,2) NOT NULL,
                origin VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL,
                tenant_id CHAR(36) NOT NULL,
                workspace_id CHAR(36) NULL,
                mission_id CHAR(36) NULL,
                promoted_from CHAR(36) NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_memory_records_tenant (tenant_id),
                INDEX idx_memory_records_subject_key (tenant_id, workspace_id, subject_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS memory_record_source_missions (
                memory_record_id CHAR(36) NOT NULL,
                mission_id CHAR(36) NOT NULL,
                PRIMARY KEY (memory_record_id, mission_id),
                CONSTRAINT fk_memory_record_source_missions_record FOREIGN KEY (memory_record_id) REFERENCES memory_records (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS knowledge_records (
                id CHAR(36) NOT NULL PRIMARY KEY,
                area VARCHAR(50) NOT NULL,
                source_path VARCHAR(500) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                version INT NOT NULL,
                previous_version_id CHAR(36) NULL,
                tenant_id CHAR(36) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_knowledge_records_lineage (tenant_id, source_path, version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS digital_twins (
                id CHAR(36) NOT NULL PRIMARY KEY,
                subject_type VARCHAR(20) NOT NULL,
                external_ref VARCHAR(255) NOT NULL,
                state JSON NOT NULL,
                last_synced_at DATETIME NOT NULL,
                tenant_id CHAR(36) NOT NULL,
                CONSTRAINT uniq_digital_twins_subject UNIQUE (subject_type, external_ref)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS pinned_memory_subjects (
                subject_key VARCHAR(255) NOT NULL,
                workspace_id CHAR(36) NOT NULL,
                tenant_id CHAR(36) NOT NULL,
                actor VARCHAR(255) NOT NULL,
                pinned_at DATETIME NOT NULL,
                PRIMARY KEY (subject_key, workspace_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }
}
