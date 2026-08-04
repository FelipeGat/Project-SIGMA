<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\IdentityEngine\Domain\Event\DomainEvent;

/**
 * Usado por todo aggregate que produz eventos de domínio (Identity,
 * Role, RoleAssignment). Os eventos ficam retidos até serem retirados
 * explicitamente — nunca publicados sozinhos pelo Domain (ADR-0062).
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
