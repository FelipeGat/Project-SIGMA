<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\MarkMemoryContradicted;
use Sigma\MemoryEngine\Application\UseCase\PinMemorySubject;
use Sigma\MemoryEngine\Application\UseCase\ReactivateMemoryRecord;
use Sigma\MemoryEngine\Application\UseCase\RetractMemoryRecord;
use Sigma\MemoryEngine\Application\UseCase\UnpinMemorySubject;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MemoryRecordStatus;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryMemoryRecordRepository;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryPinnedMemorySubjectRepository;

final class MemoryRecordGovernanceTest extends TestCase
{
    public function test_contradiction_reactivation_and_retraction_flow_through_the_repository(): void
    {
        $repository = new InMemoryMemoryRecordRepository();
        $eventBus = new InMemoryEventBus();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $record = MemoryRecord::observe('client.x', 'fato', 0.8, 'Manual', TenantId::generate(), WorkspaceId::generate(), MissionId::generate(), $now);
        $record->pullDomainEvents();
        $repository->save($record);
        $contradictor = MemoryRecord::observe('client.x', 'oposto', 0.8, 'Manual', TenantId::generate(), WorkspaceId::generate(), MissionId::generate(), $now);

        (new MarkMemoryContradicted($repository, $eventBus))->execute($record->id(), $contradictor->id());
        self::assertSame(MemoryRecordStatus::Deprecated, $repository->find($record->id())?->status());

        (new ReactivateMemoryRecord($repository, $eventBus))->execute($record->id());
        self::assertSame(MemoryRecordStatus::Active, $repository->find($record->id())?->status());

        (new RetractMemoryRecord($repository, $eventBus))->execute($record->id(), 'user:felipe');
        self::assertSame(MemoryRecordStatus::Retracted, $repository->find($record->id())?->status());
    }

    public function test_pinning_and_unpinning_a_subject(): void
    {
        $pinnedSubjects = new InMemoryPinnedMemorySubjectRepository();
        $eventBus = new InMemoryEventBus();
        $tenantId = TenantId::generate();
        $workspaceId = WorkspaceId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $published = [];
        $eventBus->subscribe('memory.subject_pinned', function (array $payload) use (&$published): void {
            $published[] = $payload;
        });

        (new PinMemorySubject($pinnedSubjects, $eventBus))->execute('client.sensitive', $workspaceId, $tenantId, 'user:felipe', $now);

        self::assertNotNull($pinnedSubjects->find('client.sensitive', $workspaceId, $tenantId));
        self::assertCount(1, $published);
        self::assertSame('client.sensitive', $published[0]['subjectKey']);

        (new UnpinMemorySubject($pinnedSubjects))->execute('client.sensitive', $workspaceId, $tenantId);
        self::assertNull($pinnedSubjects->find('client.sensitive', $workspaceId, $tenantId));
    }
}
