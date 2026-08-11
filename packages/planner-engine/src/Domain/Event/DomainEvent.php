<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain\Event;

interface DomainEvent
{
    public function name(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
