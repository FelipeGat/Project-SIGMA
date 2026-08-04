<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\KnowledgeRecordIndexed;

/**
 * Um fato curado sobre o negócio da Alfa, alimentado por `/knowledge`
 * (MEMORY_MODEL.md — KnowledgeRecord). Imutável — nunca editado em
 * lugar (ADR-0086): toda atualização cria uma nova versão via
 * reviseFrom(), a versão anterior permanece intacta e consultável.
 * `sourcePath` é a chave de lineage entre todas as versões de um mesmo
 * arquivo de origem.
 */
final class KnowledgeRecord
{
    use RecordsDomainEvents;

    private function __construct(
        private readonly KnowledgeRecordId $id,
        private readonly string $area,
        private readonly string $sourcePath,
        private readonly string $title,
        private readonly string $content,
        private readonly int $version,
        private readonly ?KnowledgeRecordId $previousVersionId,
        private readonly TenantId $tenantId,
    ) {
    }

    public static function index(
        string $area,
        string $sourcePath,
        string $title,
        string $content,
        TenantId $tenantId,
    ): self {
        self::assertArea($area);
        self::assertSourcePath($sourcePath);

        $record = new self(KnowledgeRecordId::generate(), $area, $sourcePath, $title, $content, 1, null, $tenantId);
        $record->record(new KnowledgeRecordIndexed($record->id, $area, $sourcePath, 1));

        return $record;
    }

    /**
     * Cria a próxima versão a partir de uma já existente — `area` e
     * `sourcePath` são herdados, nunca redefinidos (a linhagem é
     * sempre do mesmo arquivo de origem). A versão anterior não é
     * alterada nem apagada.
     */
    public static function reviseFrom(self $previous, string $title, string $content): self
    {
        $record = new self(
            KnowledgeRecordId::generate(),
            $previous->area,
            $previous->sourcePath,
            $title,
            $content,
            $previous->version + 1,
            $previous->id,
            $previous->tenantId,
        );
        $record->record(new KnowledgeRecordIndexed($record->id, $record->area, $record->sourcePath, $record->version));

        return $record;
    }

    /**
     * Reidrata um KnowledgeRecord já existente a partir de dados
     * persistidos (Infrastructure, Release 4B) — nunca dispara eventos
     * de domínio.
     */
    public static function reconstitute(
        KnowledgeRecordId $id,
        string $area,
        string $sourcePath,
        string $title,
        string $content,
        int $version,
        ?KnowledgeRecordId $previousVersionId,
        TenantId $tenantId,
    ): self {
        return new self($id, $area, $sourcePath, $title, $content, $version, $previousVersionId, $tenantId);
    }

    public function id(): KnowledgeRecordId
    {
        return $this->id;
    }

    public function area(): string
    {
        return $this->area;
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function previousVersionId(): ?KnowledgeRecordId
    {
        return $this->previousVersionId;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    private static function assertArea(string $area): void
    {
        if (trim($area) === '') {
            throw new SigmaException('area não pode ser vazio.', 'memory.invalid_knowledge_area');
        }
    }

    private static function assertSourcePath(string $sourcePath): void
    {
        if (trim($sourcePath) === '') {
            throw new SigmaException('sourcePath não pode ser vazio.', 'memory.invalid_source_path');
        }
    }
}
