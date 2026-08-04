<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\MemoryEngine\Domain\Event\DomainEvent;

/**
 * Usado por todo aggregate que produz eventos de domínio
 * (ContextMemory, MemoryRecord, KnowledgeRecord, DigitalTwin). Os
 * eventos ficam retidos até serem retirados explicitamente — nunca
 * publicados sozinhos pelo Domain (mesmo princípio de ADR-0062,
 * aplicado ao Memory Engine).
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
