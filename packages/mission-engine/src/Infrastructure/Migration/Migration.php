<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Infrastructure\Migration;

/** PDO puro + runner próprio — mesma decisão de identity-engine/memory-engine (ADR-0061). */
interface Migration
{
    /** Identificador único e ordenável — ex: "0001_create_schema". */
    public function version(): string;

    public function up(\PDO $pdo): void;
}
