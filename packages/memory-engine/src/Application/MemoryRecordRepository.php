<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Application;

use Sigma\MemoryEngine\Domain\MemoryRecordId;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

interface MemoryRecordRepository
{
    public function save(MemoryRecord $record): void;

    public function find(MemoryRecordId $id): ?MemoryRecord;

    /**
     * Missions (fora da própria `$record`) que também têm um
     * MemoryRecord Active com este subjectKey neste Workspace — a
     * evidência estrutural que `evaluatePromotionToProject()` precisa,
     * já resolvida pelo repositório (Domain nunca consulta sozinho).
     *
     * @return list<\Sigma\MemoryEngine\Domain\MissionId>
     */
    public function findReinforcingMissionIds(string $subjectKey, WorkspaceId $workspaceId, MemoryRecordId $excluding, TenantId $tenantId): array;

    /**
     * Workspaces (fora do da própria `$record`) onde este **mesmo**
     * subjectKey (comparação exata de string, nunca um padrão
     * generalizado — ver "Não existe ainda" na Proposal 4B) existe
     * como MemoryRecord Project Active — evidência estrutural para
     * `evaluatePromotionToLongTerm()`. A geração de uma chave
     * generalizada de fato (`client.*.x`) e sua correspondência contra
     * várias chaves específicas continuam fora de escopo desta
     * Release; quem chama `EvaluateMemoryPromotion` decide a chave
     * generalizada do registro resultante, este método só encontra
     * repetição exata.
     *
     * @return list<WorkspaceId>
     */
    public function findReinforcingWorkspaceIds(string $subjectKey, WorkspaceId $excluding, TenantId $tenantId): array;
}
