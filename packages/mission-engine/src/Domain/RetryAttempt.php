<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/** Um registro de tentativa — nunca um contador solto (ADR-0091). */
final class RetryAttempt
{
    public function __construct(
        public readonly int $attemptNumber,
        public readonly string $reason,
        public readonly \DateTimeImmutable $at,
    ) {
    }
}
