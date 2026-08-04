<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * O registro de uma ação de compensação (ADR-0091). `action` é texto
 * livre nesta Release — nenhuma taxonomia fechada ainda.
 */
final class Compensation
{
    public function __construct(
        public readonly SubtaskId $subtaskId,
        public readonly string $action,
        public readonly \DateTimeImmutable $at,
        public readonly CompensationResult $result,
    ) {
    }
}
