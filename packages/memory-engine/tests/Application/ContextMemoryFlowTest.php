<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\CloseAndDistillContextMemory;
use Sigma\MemoryEngine\Application\UseCase\StartContextMemory;
use Sigma\MemoryEngine\Domain\DistilledFact;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryContextMemoryRepository;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryMemoryRecordRepository;

final class ContextMemoryFlowTest extends TestCase
{
    public function test_starting_closing_and_distilling_publishes_every_event_via_the_event_bus(): void
    {
        $eventBus = new InMemoryEventBus();
        $published = [];
        foreach (['context_memory.started', 'context_memory.closed', 'memory.record_observed'] as $eventName) {
            $eventBus->subscribe($eventName, function (array $payload) use (&$published, $eventName): void {
                $published[] = $eventName;
            });
        }

        $contextMemories = new InMemoryContextMemoryRepository();
        $memoryRecords = new InMemoryMemoryRecordRepository();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $start = new StartContextMemory($contextMemories, $eventBus);
        $contextMemory = $start->execute(TenantId::generate(), WorkspaceId::generate(), MissionId::generate(), 'WhatsApp', $now);

        $close = new CloseAndDistillContextMemory($contextMemories, $memoryRecords, $eventBus);
        $records = $close->execute(
            $contextMemory->id(),
            [new DistilledFact('client.discount', 'Cliente sempre pede desconto', 0.9)],
            $now,
        );

        self::assertCount(1, $records);
        self::assertInstanceOf(MemoryRecord::class, $records[0]);
        self::assertSame(['context_memory.started', 'context_memory.closed', 'memory.record_observed'], $published);
    }
}
