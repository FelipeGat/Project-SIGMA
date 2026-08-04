<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\IndexKnowledgeFromFile;
use Sigma\MemoryEngine\Application\UseCase\ReviseKnowledgeFromFile;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryKnowledgeRecordRepository;

final class KnowledgeIndexingTest extends TestCase
{
    public function test_indexing_then_revising_produces_two_versions(): void
    {
        $repository = new InMemoryKnowledgeRecordRepository();
        $eventBus = new InMemoryEventBus();
        $tenantId = TenantId::generate();

        $v1 = (new IndexKnowledgeFromFile($repository, $eventBus))
            ->execute('clientes', 'knowledge/clientes/brenno.md', 'Brenno', 'conteúdo v1', $tenantId);
        self::assertSame(1, $v1->version());

        $v2 = (new ReviseKnowledgeFromFile($repository, $eventBus))
            ->execute('knowledge/clientes/brenno.md', 'Brenno', 'conteúdo v2', $tenantId);
        self::assertSame(2, $v2->version());

        self::assertSame(2, $repository->findLatestBySourcePath('knowledge/clientes/brenno.md', $tenantId)?->version());
    }

    public function test_revising_a_source_path_never_indexed_throws(): void
    {
        $repository = new InMemoryKnowledgeRecordRepository();
        $eventBus = new InMemoryEventBus();

        $this->expectException(SigmaException::class);
        (new ReviseKnowledgeFromFile($repository, $eventBus))->execute('knowledge/never.md', 'X', 'Y', TenantId::generate());
    }
}
