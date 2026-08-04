<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Migration;

/**
 * PDO puro + runner próprio — decisão da Proposal da Release 3B, sem
 * Doctrine DBAL, mantendo o precedente framework-agnóstico de
 * packages/kernel.
 */
interface Migration
{
    /** Identificador único e ordenável — ex: "0001_create_schema". */
    public function version(): string;

    public function up(\PDO $pdo): void;
}
