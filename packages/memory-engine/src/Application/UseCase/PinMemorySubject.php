<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\PinnedMemorySubject;
use Sigma\MemoryEngine\Application\PinnedMemorySubjectRepository;
use Sigma\MemoryEngine\Domain\Event\MemorySubjectPinned;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

/**
 * `MemorySubjectPinned` não nasce de um método de `Domain/` (ver
 * Decision Log da Release 4A) — é publicado diretamente aqui, na
 * Application, único ponto que sabe fixar um subjectKey.
 */
final class PinMemorySubject
{
    public function __construct(
        private readonly PinnedMemorySubjectRepository $pinnedSubjects,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(string $subjectKey, WorkspaceId $workspaceId, TenantId $tenantId, string $actor, \DateTimeImmutable $now): void
    {
        $pin = new PinnedMemorySubject($subjectKey, $workspaceId, $tenantId, $actor, $now);
        $this->pinnedSubjects->save($pin);

        $event = new MemorySubjectPinned($subjectKey, $workspaceId, $actor);
        $this->eventBus->publish($event->name(), $event->payload());
    }
}
