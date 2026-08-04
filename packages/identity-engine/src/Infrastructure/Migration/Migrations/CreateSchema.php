<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Migration\Migrations;

use Sigma\IdentityEngine\Infrastructure\Migration\Migration;

/**
 * As doze tabelas de IDENTITY_MODEL.md — `tenant_id` obrigatório desde
 * esta primeira migration em toda tabela aplicável (ADR-0021), exceto
 * na própria `tenants`. `identities` é uma correção sobre a lista
 * original da Proposal de 3B (que não previa esta tabela) — descoberta
 * durante a implementação, registrada no Decision Log da Release 3B:
 * Identity (ADR-0064) é o agregado raiz e precisa do próprio estado
 * persistido (`active`), distinto de `users`.
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
            CREATE TABLE IF NOT EXISTS tenants (
                id CHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS companies (
                id CHAR(36) NOT NULL PRIMARY KEY,
                tenant_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_companies_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workspaces (
                id CHAR(36) NOT NULL PRIMARY KEY,
                company_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_workspaces_company FOREIGN KEY (company_id) REFERENCES companies (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) NOT NULL PRIMARY KEY,
                tenant_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT uniq_users_tenant_email UNIQUE (tenant_id, email),
                CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS identities (
                id CHAR(36) NOT NULL PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                tenant_id CHAR(36) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                CONSTRAINT uniq_identities_user UNIQUE (user_id),
                CONSTRAINT fk_identities_user FOREIGN KEY (user_id) REFERENCES users (id),
                CONSTRAINT fk_identities_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS teams (
                id CHAR(36) NOT NULL PRIMARY KEY,
                company_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_teams_company FOREIGN KEY (company_id) REFERENCES companies (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS team_memberships (
                team_id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                PRIMARY KEY (team_id, user_id),
                CONSTRAINT fk_team_memberships_team FOREIGN KEY (team_id) REFERENCES teams (id),
                CONSTRAINT fk_team_memberships_user FOREIGN KEY (user_id) REFERENCES users (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workspace_memberships (
                workspace_id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                PRIMARY KEY (workspace_id, user_id),
                CONSTRAINT fk_workspace_memberships_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id),
                CONSTRAINT fk_workspace_memberships_user FOREIGN KEY (user_id) REFERENCES users (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS roles (
                id CHAR(36) NOT NULL PRIMARY KEY,
                tenant_id CHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                autonomy_capabilities JSON NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id CHAR(36) NOT NULL,
                permission_key VARCHAR(255) NOT NULL,
                PRIMARY KEY (role_id, permission_key),
                CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS role_assignments (
                role_id CHAR(36) NOT NULL,
                subject_type VARCHAR(10) NOT NULL,
                subject_id CHAR(36) NOT NULL,
                scope_type VARCHAR(20) NOT NULL,
                scope_id CHAR(36) NOT NULL,
                revoked TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (role_id, subject_type, subject_id, scope_type, scope_id),
                CONSTRAINT fk_role_assignments_role FOREIGN KEY (role_id) REFERENCES roles (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS sessions (
                id CHAR(36) NOT NULL PRIMARY KEY,
                identity_id CHAR(36) NOT NULL,
                issued_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                workspace_id CHAR(36) NULL,
                CONSTRAINT fk_sessions_identity FOREIGN KEY (identity_id) REFERENCES identities (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }
}
