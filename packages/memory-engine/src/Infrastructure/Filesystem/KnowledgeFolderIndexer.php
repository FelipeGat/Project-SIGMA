<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Filesystem;

use Sigma\MemoryEngine\Application\KnowledgeRecordRepository;
use Sigma\MemoryEngine\Application\UseCase\IndexKnowledgeFromFile;
use Sigma\MemoryEngine\Application\UseCase\ReviseKnowledgeFromFile;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\TenantId;

/**
 * Indexação real de `/knowledge` (ADR-0080 — busca textual simples,
 * sem semântica). `sourcePath` é o caminho relativo à raiz de
 * `/knowledge` (ex: "clientes/brenno.md") — a mesma área já usada por
 * `area` (o primeiro segmento do caminho).
 */
final class KnowledgeFolderIndexer
{
    public function __construct(
        private readonly KnowledgeRecordRepository $knowledgeRecords,
        private readonly IndexKnowledgeFromFile $index,
        private readonly ReviseKnowledgeFromFile $revise,
    ) {
    }

    /** @return list<KnowledgeRecord> os registros criados ou revisados nesta chamada — arquivos sem mudança não entram na lista. */
    public function indexAll(string $knowledgeRootPath, TenantId $tenantId): array
    {
        $touched = [];

        foreach ($this->findMarkdownFiles($knowledgeRootPath) as $absolutePath) {
            $sourcePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($knowledgeRootPath))), '/');
            $area = explode('/', $sourcePath)[0] ?? 'geral';
            $content = file_get_contents($absolutePath) ?: '';
            $title = $this->extractTitle($content) ?? basename($absolutePath, '.md');

            $existing = $this->knowledgeRecords->findLatestBySourcePath($sourcePath, $tenantId);

            if ($existing === null) {
                $touched[] = $this->index->execute($area, $sourcePath, $title, $content, $tenantId);

                continue;
            }

            if ($existing->content() === $content && $existing->title() === $title) {
                continue;
            }

            $touched[] = $this->revise->execute($sourcePath, $title, $content, $tenantId);
        }

        return $touched;
    }

    /** @return list<string> */
    private function findMarkdownFiles(string $rootPath): array
    {
        if (!is_dir($rootPath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function extractTitle(string $content): ?string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
