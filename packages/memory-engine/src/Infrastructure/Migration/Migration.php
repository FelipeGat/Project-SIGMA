<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Migration;

/** PDO puro + runner próprio — mesma decisão de packages/identity-engine (Release 3B). */
interface Migration
{
    /** Identificador único e ordenável — ex: "0001_create_schema". */
    public function version(): string;

    public function up(\PDO $pdo): void;
}
