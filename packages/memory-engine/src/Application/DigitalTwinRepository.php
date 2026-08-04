<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

interface DigitalTwinRepository
{
    public function save(DigitalTwin $twin): void;

    /**
     * `externalRef` já é um UUID globalmente único (gerado por
     * `Sigma\Core\Id`, ver `identity.created`/`IdentityCreated`) —
     * este lookup não filtra por Tenant como as demais consultas do
     * SIGMA costumam fazer (ADR-0021), porque a unicidade do próprio
     * valor já garante o isolamento; o `tenantId` do Twin encontrado
     * vem do próprio registro, não é um critério de busca. Decisão
     * registrada no Decision Log da Release 4B.
     */
    public function findBySubjectTypeAndExternalRef(TwinSubjectType $subjectType, string $externalRef): ?DigitalTwin;
}
