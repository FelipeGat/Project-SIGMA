<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\MemoryDeprecated;
use Sigma\MemoryEngine\Domain\Event\MemoryPromoted;
use Sigma\MemoryEngine\Domain\Event\MemoryReactivated;
use Sigma\MemoryEngine\Domain\Event\MemoryRecordObserved;
use Sigma\MemoryEngine\Domain\Event\MemoryRetracted;

/**
 * Um fato experiencial observado pelo SIGMA — a unidade dos três
 * níveis de Memory (ADR-0022). Promoção gated por dois critérios
 * independentes — estrutura (repetição/generalização, ADR-0081) e
 * `confidence` (ADR-0084) — ambos exigidos, nenhum substitui o outro.
 * Pisos e mecânica completa em MEMORY_PROMOTION_RULES.md.
 *
 * Nunca consulta um repositório por conta própria: quem chama já
 * resolveu os dados necessários para avaliar repetição/generalização
 * (Proposal 4A — Arquitetura).
 */
final class MemoryRecord
{
    use RecordsDomainEvents;

    /** Piso mínimo de confidence para um MemoryRecord sequer ser criado (MEMORY_PROMOTION_RULES.md). */
    public const MINIMUM_CONFIDENCE_TO_OBSERVE = 0.50;

    /** Piso mínimo de confidence para Operational → Project. */
    public const MINIMUM_CONFIDENCE_FOR_PROJECT = 0.70;

    /** Piso mínimo de confidence para Project → LongTerm. */
    public const MINIMUM_CONFIDENCE_FOR_LONG_TERM = 0.90;

    /** @param list<MissionId> $sourceMissionIds */
    private function __construct(
        private readonly MemoryRecordId $id,
        private readonly MemoryLevel $level,
        private readonly string $subjectKey,
        private readonly string $content,
        private float $confidence,
        private readonly string $origin,
        private MemoryRecordStatus $status,
        private readonly TenantId $tenantId,
        private readonly ?WorkspaceId $workspaceId,
        private readonly ?MissionId $missionId,
        private array $sourceMissionIds,
        private readonly ?MemoryRecordId $promotedFrom,
    ) {
    }

    public static function observe(
        string $subjectKey,
        string $content,
        float $confidence,
        string $origin,
        TenantId $tenantId,
        WorkspaceId $workspaceId,
        MissionId $missionId,
        \DateTimeImmutable $now,
    ): self {
        self::assertSubjectKey($subjectKey);
        self::assertContent($content);
        self::assertConfidence($confidence);
        self::assertOrigin($origin);

        $record = new self(
            MemoryRecordId::generate(),
            MemoryLevel::Operational,
            $subjectKey,
            $content,
            $confidence,
            $origin,
            MemoryRecordStatus::Active,
            $tenantId,
            $workspaceId,
            $missionId,
            [$missionId],
            null,
        );
        $record->record(new MemoryRecordObserved($record->id, $subjectKey, $workspaceId, $missionId, $confidence, $origin));

        return $record;
    }

    /**
     * Reidrata um MemoryRecord já existente a partir de dados
     * persistidos (Infrastructure, Release 4B) — nunca dispara eventos
     * de domínio.
     *
     * @param list<MissionId> $sourceMissionIds
     */
    public static function reconstitute(
        MemoryRecordId $id,
        MemoryLevel $level,
        string $subjectKey,
        string $content,
        float $confidence,
        string $origin,
        MemoryRecordStatus $status,
        TenantId $tenantId,
        ?WorkspaceId $workspaceId,
        ?MissionId $missionId,
        array $sourceMissionIds,
        ?MemoryRecordId $promotedFrom,
    ): self {
        return new self(
            $id,
            $level,
            $subjectKey,
            $content,
            $confidence,
            $origin,
            $status,
            $tenantId,
            $workspaceId,
            $missionId,
            $sourceMissionIds,
            $promotedFrom,
        );
    }

    public function id(): MemoryRecordId
    {
        return $this->id;
    }

    public function level(): MemoryLevel
    {
        return $this->level;
    }

    public function subjectKey(): string
    {
        return $this->subjectKey;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function status(): MemoryRecordStatus
    {
        return $this->status;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function workspaceId(): ?WorkspaceId
    {
        return $this->workspaceId;
    }

    public function missionId(): ?MissionId
    {
        return $this->missionId;
    }

    /** @return list<MissionId> */
    public function sourceMissionIds(): array
    {
        return $this->sourceMissionIds;
    }

    public function promotedFrom(): ?MemoryRecordId
    {
        return $this->promotedFrom;
    }

    /**
     * Operational → Project: repetição (mesmo subjectKey, 2+ Missions
     * distintas no mesmo Workspace) + confidence no piso. Nenhum dos
     * dois gates substitui o outro — retorna null se qualquer um
     * faltar, nunca lança exceção nesse caso (estrutura insuficiente
     * ou confidence insuficiente é o resultado esperado, não um erro).
     *
     * @param list<MissionId> $reinforcingMissionIds Missions que também observaram este subjectKey, já resolvidas pelo chamador
     */
    public function evaluatePromotionToProject(array $reinforcingMissionIds, \DateTimeImmutable $now): ?self
    {
        if ($this->level !== MemoryLevel::Operational) {
            throw new SigmaException(
                'Só um MemoryRecord Operational pode ser avaliado para promoção a Project.',
                'memory.invalid_promotion_level',
            );
        }

        if ($this->status !== MemoryRecordStatus::Active) {
            return null;
        }

        $allMissionIds = self::deduplicate([...$this->sourceMissionIds, ...$reinforcingMissionIds]);

        if (count($allMissionIds) < 2) {
            return null;
        }

        if ($this->confidence < self::MINIMUM_CONFIDENCE_FOR_PROJECT) {
            return null;
        }

        $promoted = new self(
            MemoryRecordId::generate(),
            MemoryLevel::Project,
            $this->subjectKey,
            $this->content,
            $this->confidence,
            $this->origin,
            MemoryRecordStatus::Active,
            $this->tenantId,
            $this->workspaceId,
            null,
            $allMissionIds,
            $this->id,
        );
        $promoted->record(new MemoryPromoted(
            $promoted->id,
            $this->subjectKey,
            MemoryLevel::Operational,
            MemoryLevel::Project,
            $this->confidence,
            $this->id,
        ));

        return $promoted;
    }

    /**
     * Project → LongTerm: generalização (mesmo subjectKey ou forma
     * generalizada, 2+ Workspaces distintos) + confidence no piso mais
     * alto. A geração da chave generalizada é decisão de Implementation
     * (Release 4B) — este método só recebe o resultado já resolvido.
     *
     * @param list<WorkspaceId> $reinforcingWorkspaceIds Workspaces onde este subjectKey (ou generalizado) também é Project, já resolvidos pelo chamador
     */
    public function evaluatePromotionToLongTerm(
        string $generalizedSubjectKey,
        array $reinforcingWorkspaceIds,
        \DateTimeImmutable $now,
    ): ?self {
        if ($this->level !== MemoryLevel::Project) {
            throw new SigmaException(
                'Só um MemoryRecord Project pode ser avaliado para promoção a LongTerm.',
                'memory.invalid_promotion_level',
            );
        }

        if ($this->workspaceId === null) {
            throw new SigmaException('MemoryRecord Project sem workspaceId é um estado inválido.', 'memory.invalid_state');
        }

        self::assertSubjectKey($generalizedSubjectKey);

        if ($this->status !== MemoryRecordStatus::Active) {
            return null;
        }

        $allWorkspaceIds = self::deduplicate([$this->workspaceId, ...$reinforcingWorkspaceIds]);

        if (count($allWorkspaceIds) < 2) {
            return null;
        }

        if ($this->confidence < self::MINIMUM_CONFIDENCE_FOR_LONG_TERM) {
            return null;
        }

        $promoted = new self(
            MemoryRecordId::generate(),
            MemoryLevel::LongTerm,
            $generalizedSubjectKey,
            $this->content,
            $this->confidence,
            $this->origin,
            MemoryRecordStatus::Active,
            $this->tenantId,
            null,
            null,
            $this->sourceMissionIds,
            $this->id,
        );
        $promoted->record(new MemoryPromoted(
            $promoted->id,
            $generalizedSubjectKey,
            MemoryLevel::Project,
            MemoryLevel::LongTerm,
            $this->confidence,
            $this->id,
        ));

        return $promoted;
    }

    /**
     * Contradição automática (MEMORY_PROMOTION_RULES.md — "Quando uma
     * Memory desce"). Nunca apaga o registro — só marca como não mais
     * confiável. Pode voltar a Active via reactivate().
     */
    public function markContradicted(MemoryRecordId $contradictedBy): void
    {
        if ($this->status !== MemoryRecordStatus::Active) {
            throw new SigmaException('Só um MemoryRecord Active pode ser marcado como contradito.', 'memory.invalid_status_transition');
        }

        $this->status = MemoryRecordStatus::Deprecated;
        $this->record(new MemoryDeprecated($this->id, $this->subjectKey, $contradictedBy));
    }

    /** Reforçado sem nova contradição — volta a Active. Retracted nunca reativa sozinho. */
    public function reactivate(): void
    {
        if ($this->status !== MemoryRecordStatus::Deprecated) {
            throw new SigmaException('Só um MemoryRecord Deprecated pode ser reativado.', 'memory.invalid_status_transition');
        }

        $this->status = MemoryRecordStatus::Active;
        $this->record(new MemoryReactivated($this->id, $this->subjectKey));
    }

    /** Retração — só por ação humana explícita (Permission memory.block_promotion, checada na Application 4B). */
    public function retract(string $actor): void
    {
        if ($this->status === MemoryRecordStatus::Retracted) {
            throw new SigmaException('MemoryRecord já está retratado.', 'memory.already_retracted');
        }

        if (trim($actor) === '') {
            throw new SigmaException('actor não pode ser vazio.', 'memory.invalid_actor');
        }

        $this->status = MemoryRecordStatus::Retracted;
        $this->record(new MemoryRetracted($this->id, $this->subjectKey, $actor));
    }

    /** @param list<Identifier> $ids @return list<Identifier> */
    private static function deduplicate(array $ids): array
    {
        $seen = [];
        $result = [];
        foreach ($ids as $id) {
            $key = $id::class . ':' . $id->toString();
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $id;
            }
        }

        return $result;
    }

    private static function assertSubjectKey(string $subjectKey): void
    {
        if (trim($subjectKey) === '') {
            throw new SigmaException('subjectKey não pode ser vazio.', 'memory.invalid_subject_key');
        }
    }

    private static function assertContent(string $content): void
    {
        if (trim($content) === '') {
            throw new SigmaException('content não pode ser vazio.', 'memory.invalid_content');
        }
    }

    private static function assertConfidence(float $confidence): void
    {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new SigmaException('confidence precisa estar entre 0.0 e 1.0.', 'memory.invalid_confidence');
        }
    }

    private static function assertOrigin(string $origin): void
    {
        if (trim($origin) === '') {
            throw new SigmaException('origin não pode ser vazio.', 'memory.invalid_origin');
        }
    }
}
