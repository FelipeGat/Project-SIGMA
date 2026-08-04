<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application\Fake;

use Sigma\MemoryEngine\Application\DigitalTwinRepository;
use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

final class InMemoryDigitalTwinRepository implements DigitalTwinRepository
{
    /** @var array<string, DigitalTwin> */
    private array $twins = [];

    public function save(DigitalTwin $twin): void
    {
        $this->twins[$this->key($twin->subjectType(), $twin->externalRef())] = $twin;
    }

    public function findBySubjectTypeAndExternalRef(TwinSubjectType $subjectType, string $externalRef): ?DigitalTwin
    {
        return $this->twins[$this->key($subjectType, $externalRef)] ?? null;
    }

    private function key(TwinSubjectType $subjectType, string $externalRef): string
    {
        return $subjectType->value . ':' . $externalRef;
    }
}
