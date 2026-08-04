<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Infrastructure\Pdo;

use PHPUnit\Framework\TestCase;
use Sigma\MemoryEngine\Application\PinnedMemorySubject;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\ContextMemoryStatus;
use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;
use Sigma\MemoryEngine\Domain\WorkspaceId;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoContextMemoryRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoDigitalTwinRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoKnowledgeRecordRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoMemoryRecordRepository;
use Sigma\MemoryEngine\Infrastructure\Pdo\PdoPinnedMemorySubjectRepository;
use Sigma\MemoryEngine\Tests\Infrastructure\PdoTestConnection;

/**
 * Round-trip de cada repositório contra uma MariaDB real — pulado
 * explicitamente se não houver banco alcançável (ver PdoTestConnection).
 */
final class PdoRepositoriesTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = PdoTestConnection::connectOrSkip();
    }

    public function test_context_memory_round_trip(): void
    {
        $repository = new PdoContextMemoryRepository($this->pdo);
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $contextMemory = ContextMemory::start(TenantId::generate(), WorkspaceId::generate(), MissionId::generate(), 'WhatsApp', $now);
        $contextMemory->appendContent('Cliente pediu desconto.');
        $contextMemory->pullDomainEvents();
        $repository->save($contextMemory);

        $found = $repository->find($contextMemory->id());
        self::assertNotNull($found);
        self::assertSame('Cliente pediu desconto.', $found->rawContent());
        self::assertSame(ContextMemoryStatus::Active, $found->status());

        $found->close($now);
        $repository->save($found);

        self::assertSame(ContextMemoryStatus::Closed, $repository->find($contextMemory->id())?->status());
    }

    public function test_memory_record_round_trip_including_source_missions_and_promotion_queries(): void
    {
        $repository = new PdoMemoryRecordRepository($this->pdo);
        $tenantId = TenantId::generate();
        $workspaceId = WorkspaceId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceId, MissionId::generate(), $now);
        $record->pullDomainEvents();
        $repository->save($record);

        $found = $repository->find($record->id());
        self::assertNotNull($found);
        self::assertSame('client.x', $found->subjectKey());
        self::assertCount(1, $found->sourceMissionIds());

        $reinforcing = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceId, MissionId::generate(), $now);
        $reinforcing->pullDomainEvents();
        $repository->save($reinforcing);

        $missionIds = $repository->findReinforcingMissionIds('client.x', $workspaceId, $record->id(), $tenantId);
        self::assertCount(1, $missionIds);

        $promoted = $found->evaluatePromotionToProject($missionIds, $now);
        self::assertNotNull($promoted);
        $repository->save($promoted);

        self::assertSame(MemoryLevel::Project, $repository->find($promoted->id())?->level());
    }

    public function test_knowledge_record_versions_are_never_overwritten(): void
    {
        $repository = new PdoKnowledgeRecordRepository($this->pdo);
        $tenantId = TenantId::generate();

        $v1 = KnowledgeRecord::index('clientes', 'knowledge/clientes/brenno.md', 'Brenno', 'v1', $tenantId);
        $v1->pullDomainEvents();
        $repository->save($v1);

        $v2 = KnowledgeRecord::reviseFrom($v1, 'Brenno', 'v2');
        $v2->pullDomainEvents();
        $repository->save($v2);

        $latest = $repository->findLatestBySourcePath('knowledge/clientes/brenno.md', $tenantId);
        self::assertSame(2, $latest?->version());
        self::assertSame('v2', $latest?->content());
    }

    public function test_digital_twin_round_trip_and_lookup_by_external_ref(): void
    {
        $repository = new PdoDigitalTwinRepository($this->pdo);
        $tenantId = TenantId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $twin = DigitalTwin::project(TwinSubjectType::User, 'identity-123', ['userId' => 'user-456'], $tenantId, $now);
        $twin->pullDomainEvents();
        $repository->save($twin);

        $found = $repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'identity-123');
        self::assertNotNull($found);
        self::assertSame('user-456', $found->state()['userId']);

        $found->applyProjection(['userId' => 'user-456', 'workspaceId' => 'workspace-999'], $now);
        $found->pullDomainEvents();
        $repository->save($found);

        self::assertSame('workspace-999', $repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'identity-123')?->state()['workspaceId']);
    }

    public function test_pinned_memory_subject_round_trip_and_delete(): void
    {
        $repository = new PdoPinnedMemorySubjectRepository($this->pdo);
        $subjectKey = 'client.sensitive';
        $workspaceId = WorkspaceId::generate();
        $tenantId = TenantId::generate();

        $repository->save(new PinnedMemorySubject($subjectKey, $workspaceId, $tenantId, 'user:felipe', new \DateTimeImmutable()));
        self::assertNotNull($repository->find($subjectKey, $workspaceId, $tenantId));

        $repository->delete($subjectKey, $workspaceId, $tenantId);
        self::assertNull($repository->find($subjectKey, $workspaceId, $tenantId));
    }
}
