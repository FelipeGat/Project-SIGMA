<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\MissionEngine\Domain\Event\DomainEvent;

/**
 * Usado pelo aggregate `Mission` para reter e retirar eventos
 * pendentes — nunca publicados sozinhos pelo Domain (mesmo princípio
 * de ADR-0062, aplicado ao Mission Engine).
 */
trait RecordsDomainEvents
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<DomainEvent> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
