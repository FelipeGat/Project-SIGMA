<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\EvaluateMemoryPromotion;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryMemoryRecordRepository;

final class EvaluateMemoryPromotionTest extends TestCase
{
    public function test_promotion_resolves_reinforcing_evidence_from_the_repository_and_promotes(): void
    {
        $repository = new InMemoryMemoryRecordRepository();
        $eventBus = new InMemoryEventBus();
        $tenantId = TenantId::generate();
        $workspaceA = WorkspaceId::generate();
        $workspaceB = WorkspaceId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $first = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceA, MissionId::generate(), $now);
        $first->pullDomainEvents();
        $repository->save($first);

        // Reforço: mesmo subjectKey/Workspace, Mission diferente.
        $second = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceA, MissionId::generate(), $now);
        $second->pullDomainEvents();
        $repository->save($second);

        $useCase = new EvaluateMemoryPromotion($repository, $eventBus);
        $promoted = $useCase->toProject($first->id(), $now);

        self::assertNotNull($promoted);
        self::assertSame(MemoryLevel::Project, $promoted->level());
        self::assertNotNull($repository->find($promoted->id()));

        // Reforço para LongTerm: mesmo subjectKey em outro Workspace, também Project.
        $thirdOperational = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceB, MissionId::generate(), $now);
        $thirdOperational->pullDomainEvents();
        $repository->save($thirdOperational);
        $fourthOperational = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $tenantId, $workspaceB, MissionId::generate(), $now);
        $fourthOperational->pullDomainEvents();
        $repository->save($fourthOperational);
        $otherProject = $useCase->toProject($thirdOperational->id(), $now);
        self::assertNotNull($otherProject);

        $longTerm = $useCase->toLongTerm($promoted->id(), 'client.*.x', $now);

        self::assertNotNull($longTerm);
        self::assertSame(MemoryLevel::LongTerm, $longTerm->level());
    }

    public function test_promotion_without_enough_reinforcement_returns_null_and_persists_source_unchanged(): void
    {
        $repository = new InMemoryMemoryRecordRepository();
        $eventBus = new InMemoryEventBus();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', TenantId::generate(), WorkspaceId::generate(), MissionId::generate(), $now);
        $record->pullDomainEvents();
        $repository->save($record);

        $promoted = (new EvaluateMemoryPromotion($repository, $eventBus))->toProject($record->id(), $now);

        self::assertNull($promoted);
        self::assertSame(MemoryLevel::Operational, $repository->find($record->id())?->level());
    }
}
