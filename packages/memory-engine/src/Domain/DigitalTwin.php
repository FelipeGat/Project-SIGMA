<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinCreated;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinStale;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinUpdated;

/**
 * A representação viva de uma entidade externa (DIGITAL_TWIN.md).
 * Nunca é a fonte da verdade — só reflete o que já aconteceu.
 * Estritamente Event-Driven (ADR-0085): `project()` é o nome
 * deliberado da primeira criação, reforçando que mesmo a primeira
 * população nasce de um evento (Evento → Projection → Twin), nunca de
 * uma leitura direta.
 */
final class DigitalTwin
{
    use RecordsDomainEvents;

    /** @param array<string, mixed> $state */
    private function __construct(
        private readonly DigitalTwinId $id,
        private readonly TwinSubjectType $subjectType,
        private readonly string $externalRef,
        private array $state,
        private \DateTimeImmutable $lastSyncedAt,
        private readonly TenantId $tenantId,
    ) {
    }

    /** @param array<string, mixed> $state */
    public static function project(
        TwinSubjectType $subjectType,
        string $externalRef,
        array $state,
        TenantId $tenantId,
        \DateTimeImmutable $syncedAt,
    ): self {
        if (trim($externalRef) === '') {
            throw new SigmaException('externalRef não pode ser vazio.', 'memory.invalid_external_ref');
        }

        $twin = new self(DigitalTwinId::generate(), $subjectType, $externalRef, $state, $syncedAt, $tenantId);
        $twin->record(new DigitalTwinCreated($twin->id, $subjectType, $externalRef));

        return $twin;
    }

    /**
     * Reidrata um DigitalTwin já existente a partir de dados
     * persistidos (Infrastructure, Release 4B) — nunca dispara eventos
     * de domínio.
     *
     * @param array<string, mixed> $state
     */
    public static function reconstitute(
        DigitalTwinId $id,
        TwinSubjectType $subjectType,
        string $externalRef,
        array $state,
        \DateTimeImmutable $lastSyncedAt,
        TenantId $tenantId,
    ): self {
        return new self($id, $subjectType, $externalRef, $state, $lastSyncedAt, $tenantId);
    }

    public function id(): DigitalTwinId
    {
        return $this->id;
    }

    public function subjectType(): TwinSubjectType
    {
        return $this->subjectType;
    }

    public function externalRef(): string
    {
        return $this->externalRef;
    }

    /** @return array<string, mixed> */
    public function state(): array
    {
        return $this->state;
    }

    public function lastSyncedAt(): \DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    /**
     * Atualização a partir de um Semantic Event já projetado pelo
     * chamador — sempre Evento → Projection → Twin (ADR-0085).
     *
     * @param array<string, mixed> $state
     */
    public function applyProjection(array $state, \DateTimeImmutable $syncedAt): void
    {
        $this->state = $state;
        $this->lastSyncedAt = $syncedAt;
        $this->record(new DigitalTwinUpdated($this->id, $this->subjectType, $syncedAt));
    }

    /**
     * Fora da janela de refresh esperada, registra DigitalTwinStale e
     * retorna true — o disparo do `warning` no Envelope é
     * responsabilidade de quem consome o Twin (Interfaces, 4B/além).
     */
    public function checkStaleness(\DateTimeImmutable $now, \DateInterval $refreshWindow): bool
    {
        $staleAt = $this->lastSyncedAt->add($refreshWindow);

        if ($now < $staleAt) {
            return false;
        }

        $this->record(new DigitalTwinStale($this->id, $this->subjectType, $this->lastSyncedAt));

        return true;
    }
}
