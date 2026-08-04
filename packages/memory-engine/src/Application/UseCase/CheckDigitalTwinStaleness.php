<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\IEventBus;
use Sigma\MemoryEngine\Application\DigitalTwinRepository;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

final class CheckDigitalTwinStaleness
{
    public function __construct(
        private readonly DigitalTwinRepository $digitalTwins,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(TwinSubjectType $subjectType, string $externalRef, \DateTimeImmutable $now, \DateInterval $refreshWindow): bool
    {
        $twin = $this->digitalTwins->findBySubjectTypeAndExternalRef($subjectType, $externalRef)
            ?? throw new SigmaException('DigitalTwin não encontrado.', 'memory.twin_not_found');

        $isStale = $twin->checkStaleness($now, $refreshWindow);

        foreach ($twin->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $isStale;
    }
}
