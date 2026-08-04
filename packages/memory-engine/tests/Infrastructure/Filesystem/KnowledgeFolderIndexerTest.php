<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Infrastructure\Filesystem;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\IndexKnowledgeFromFile;
use Sigma\MemoryEngine\Application\UseCase\ReviseKnowledgeFromFile;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Infrastructure\Filesystem\KnowledgeFolderIndexer;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryKnowledgeRecordRepository;

final class KnowledgeFolderIndexerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/sigma-knowledge-test-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/clientes', recursive: true);
        file_put_contents($this->root . '/clientes/brenno.md', "# Cliente Brenno\n\nSempre pede desconto.");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/clientes/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->root . '/clientes');
        @rmdir($this->root);
    }

    public function test_first_scan_indexes_every_markdown_file_found(): void
    {
        $indexer = $this->indexer($repository = new InMemoryKnowledgeRecordRepository());
        $tenantId = TenantId::generate();

        $touched = $indexer->indexAll($this->root, $tenantId);

        self::assertCount(1, $touched);
        self::assertSame('Cliente Brenno', $touched[0]->title());
        self::assertSame('clientes', $touched[0]->area());
        self::assertSame(1, $touched[0]->version());
    }

    public function test_second_scan_without_changes_touches_nothing(): void
    {
        $repository = new InMemoryKnowledgeRecordRepository();
        $indexer = $this->indexer($repository);
        $tenantId = TenantId::generate();

        $indexer->indexAll($this->root, $tenantId);
        $touched = $indexer->indexAll($this->root, $tenantId);

        self::assertSame([], $touched);
    }

    public function test_a_changed_file_produces_a_new_version(): void
    {
        $repository = new InMemoryKnowledgeRecordRepository();
        $indexer = $this->indexer($repository);
        $tenantId = TenantId::generate();

        $indexer->indexAll($this->root, $tenantId);
        file_put_contents($this->root . '/clientes/brenno.md', "# Cliente Brenno\n\nSempre pede desconto de 15% agora.");

        $touched = $indexer->indexAll($this->root, $tenantId);

        self::assertCount(1, $touched);
        self::assertSame(2, $touched[0]->version());
    }

    private function indexer(InMemoryKnowledgeRecordRepository $repository): KnowledgeFolderIndexer
    {
        $eventBus = new InMemoryEventBus();

        return new KnowledgeFolderIndexer(
            $repository,
            new IndexKnowledgeFromFile($repository, $eventBus),
            new ReviseKnowledgeFromFile($repository, $eventBus),
        );
    }
}
