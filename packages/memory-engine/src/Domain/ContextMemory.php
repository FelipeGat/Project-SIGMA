<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\ContextMemoryClosed;
use Sigma\MemoryEngine\Domain\Event\ContextMemoryStarted;

/**
 * O estágio bruto e efêmero de um engajamento em andamento — o que
 * está sendo dito/observado agora, antes de qualquer julgamento sobre
 * o que vale a pena virar Memory (MEMORY_MODEL.md — ContextMemory,
 * ADR-0083). Não participa da escada Operational/Project/LongTerm —
 * é destilado em zero ou mais `MemoryRecord`, nunca promovido.
 */
final class ContextMemory
{
    use RecordsDomainEvents;

    private string $rawContent = '';
    private ?\DateTimeImmutable $endedAt = null;
    private ContextMemoryStatus $status = ContextMemoryStatus::Active;

    private function __construct(
        private readonly ContextMemoryId $id,
        private readonly TenantId $tenantId,
        private readonly WorkspaceId $workspaceId,
        private readonly ?MissionId $missionId,
        private readonly string $origin,
        private readonly \DateTimeImmutable $startedAt,
    ) {
    }

    public static function start(
        TenantId $tenantId,
        WorkspaceId $workspaceId,
        ?MissionId $missionId,
        string $origin,
        \DateTimeImmutable $now,
    ): self {
        if (trim($origin) === '') {
            throw new SigmaException('origin não pode ser vazio.', 'memory.invalid_origin');
        }

        $contextMemory = new self(ContextMemoryId::generate(), $tenantId, $workspaceId, $missionId, $origin, $now);
        $contextMemory->record(new ContextMemoryStarted($contextMemory->id, $workspaceId, $missionId, $origin));

        return $contextMemory;
    }

    /**
     * Reidrata um ContextMemory já existente a partir de dados
     * persistidos (Infrastructure, Release 4B) — nunca dispara eventos
     * de domínio, ao contrário de start()/close().
     */
    public static function reconstitute(
        ContextMemoryId $id,
        TenantId $tenantId,
        WorkspaceId $workspaceId,
        ?MissionId $missionId,
        string $origin,
        string $rawContent,
        \DateTimeImmutable $startedAt,
        ?\DateTimeImmutable $endedAt,
        ContextMemoryStatus $status,
    ): self {
        $contextMemory = new self($id, $tenantId, $workspaceId, $missionId, $origin, $startedAt);
        $contextMemory->rawContent = $rawContent;
        $contextMemory->endedAt = $endedAt;
        $contextMemory->status = $status;

        return $contextMemory;
    }

    public function id(): ContextMemoryId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function workspaceId(): WorkspaceId
    {
        return $this->workspaceId;
    }

    public function missionId(): ?MissionId
    {
        return $this->missionId;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function rawContent(): string
    {
        return $this->rawContent;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function status(): ContextMemoryStatus
    {
        return $this->status;
    }

    /** Acumula conteúdo bruto do engajamento — sem estrutura imposta. */
    public function appendContent(string $chunk): void
    {
        $this->assertActive();
        $this->rawContent .= $chunk;
    }

    public function close(\DateTimeImmutable $now): void
    {
        $this->assertActive();

        $this->status = ContextMemoryStatus::Closed;
        $this->endedAt = $now;
        $this->record(new ContextMemoryClosed($this->id, $this->workspaceId, $now));
    }

    /**
     * Destila o engajamento fechado em zero ou mais `MemoryRecord`
     * `Operational` — cada fato já vem resolvido pelo chamador (o
     * algoritmo de destilação em si é Infrastructure/Application da
     * Release 4B, ver DistilledFact). Um fato abaixo do piso mínimo é
     * descartado, nunca vira registro (MEMORY_PROMOTION_RULES.md —
     * "Quando uma Memory nasce").
     *
     * Exige que este engajamento esteja associado a uma Mission —
     * Operational Memory é sempre Mission-scoped (ADR-0022); um
     * engajamento pré-Mission (`missionId` ausente) não produz
     * MemoryRecord, mesmo já destilável em outros aspectos.
     *
     * @param list<DistilledFact> $facts
     * @return list<MemoryRecord>
     */
    public function distill(array $facts, float $minimumConfidenceToObserve, \DateTimeImmutable $now): array
    {
        if ($this->status !== ContextMemoryStatus::Closed) {
            throw new SigmaException('Só um ContextMemory fechado pode ser destilado.', 'memory.context_not_closed');
        }

        if ($this->missionId === null) {
            throw new SigmaException(
                'ContextMemory sem Mission associada não produz MemoryRecord (Operational é Mission-scoped).',
                'memory.context_without_mission',
            );
        }

        $records = [];
        foreach ($facts as $fact) {
            if ($fact->confidence < $minimumConfidenceToObserve) {
                continue;
            }

            $records[] = MemoryRecord::observe(
                $fact->subjectKey,
                $fact->content,
                $fact->confidence,
                $this->origin,
                $this->tenantId,
                $this->workspaceId,
                $this->missionId,
                $now,
            );
        }

        return $records;
    }

    private function assertActive(): void
    {
        if ($this->status !== ContextMemoryStatus::Active) {
            throw new SigmaException('ContextMemory já está fechado.', 'memory.context_already_closed');
        }
    }
}
