<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\KnowledgeRecordIndexed;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

/** Cobre ADR-0086 — KnowledgeRecord é imutável, nunca editado em lugar. */
final class KnowledgeRecordTest extends TestCase
{
    private TenantId $tenantId;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
    }

    public function test_indexing_creates_version_one(): void
    {
        $record = KnowledgeRecord::index('clientes', 'knowledge/clientes/brenno.md', 'Cliente Brenno', 'conteúdo', $this->tenantId);

        self::assertSame(1, $record->version());
        self::assertNull($record->previousVersionId());

        $events = $record->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(KnowledgeRecordIndexed::class, $events[0]);
        self::assertSame(1, $events[0]->version);
    }

    public function test_revising_creates_a_new_version_without_touching_the_previous_one(): void
    {
        $v1 = KnowledgeRecord::index('clientes', 'knowledge/clientes/brenno.md', 'Cliente Brenno', 'conteúdo original', $this->tenantId);
        $originalContent = $v1->content();

        $v2 = KnowledgeRecord::reviseFrom($v1, 'Cliente Brenno', 'conteúdo atualizado');

        self::assertSame(2, $v2->version());
        self::assertTrue($v2->previousVersionId()?->equals($v1->id()));
        self::assertSame($v1->sourcePath(), $v2->sourcePath());
        self::assertSame($v1->area(), $v2->area());
        self::assertSame($originalContent, $v1->content(), 'a versão anterior nunca é alterada');
        self::assertNotSame($v1->id()->toString(), $v2->id()->toString());
    }

    public function test_a_chain_of_revisions_preserves_full_lineage(): void
    {
        $v1 = KnowledgeRecord::index('clientes', 'knowledge/clientes/brenno.md', 'Brenno', 'v1', $this->tenantId);
        $v2 = KnowledgeRecord::reviseFrom($v1, 'Brenno', 'v2');
        $v3 = KnowledgeRecord::reviseFrom($v2, 'Brenno', 'v3');

        self::assertSame(3, $v3->version());
        self::assertTrue($v3->previousVersionId()?->equals($v2->id()));
        self::assertTrue($v2->previousVersionId()?->equals($v1->id()));
    }

    public function test_indexing_with_empty_area_throws(): void
    {
        $this->expectException(SigmaException::class);
        KnowledgeRecord::index('', 'knowledge/x.md', 'Título', 'conteúdo', $this->tenantId);
    }
}
