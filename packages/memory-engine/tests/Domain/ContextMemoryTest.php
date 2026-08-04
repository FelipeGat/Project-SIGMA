<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\ContextMemoryStatus;
use Sigma\MemoryEngine\Domain\DistilledFact;
use Sigma\MemoryEngine\Domain\Event\ContextMemoryClosed;
use Sigma\MemoryEngine\Domain\Event\ContextMemoryStarted;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

final class ContextMemoryTest extends TestCase
{
    private TenantId $tenantId;
    private WorkspaceId $workspaceId;
    private MissionId $missionId;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->workspaceId = WorkspaceId::generate();
        $this->missionId = MissionId::generate();
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_starting_a_context_memory_records_the_started_event(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'WhatsApp', $this->now);

        self::assertSame(ContextMemoryStatus::Active, $contextMemory->status());
        self::assertSame('WhatsApp', $contextMemory->origin());

        $events = $contextMemory->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ContextMemoryStarted::class, $events[0]);
        self::assertSame('context_memory.started', $events[0]->name());
    }

    public function test_a_context_memory_without_a_mission_can_still_start_for_a_pre_mission_engagement(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, null, 'Telegram', $this->now);

        self::assertNull($contextMemory->missionId());
    }

    public function test_appending_content_accumulates_raw_content(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);

        $contextMemory->appendContent('Cliente perguntou sobre desconto. ');
        $contextMemory->appendContent('Cliente sempre pede desconto de 10%.');

        self::assertSame('Cliente perguntou sobre desconto. Cliente sempre pede desconto de 10%.', $contextMemory->rawContent());
    }

    public function test_appending_content_after_closed_throws(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);
        $contextMemory->close($this->now);

        $this->expectException(SigmaException::class);
        $this->expectExceptionCode(0);
        $contextMemory->appendContent('tarde demais');
    }

    public function test_closing_records_the_closed_event_and_changes_status(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);
        $contextMemory->pullDomainEvents();

        $contextMemory->close($this->now);

        self::assertSame(ContextMemoryStatus::Closed, $contextMemory->status());
        self::assertSame($this->now, $contextMemory->endedAt());

        $events = $contextMemory->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ContextMemoryClosed::class, $events[0]);
    }

    public function test_closing_an_already_closed_context_memory_throws(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);
        $contextMemory->close($this->now);

        $this->expectException(SigmaException::class);
        $contextMemory->close($this->now);
    }

    public function test_distilling_before_closing_throws(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);

        $this->expectException(SigmaException::class);
        $contextMemory->distill([], MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE, $this->now);
    }

    public function test_distilling_a_context_memory_without_a_mission_throws(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, null, 'Telegram', $this->now);
        $contextMemory->close($this->now);

        $this->expectException(SigmaException::class);
        $contextMemory->distill(
            [new DistilledFact('client.brenno.discount', 'Cliente pede desconto', 0.9)],
            MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE,
            $this->now,
        );
    }

    public function test_distilling_produces_zero_records_when_no_facts_reach_the_floor(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);
        $contextMemory->close($this->now);

        $records = $contextMemory->distill(
            [new DistilledFact('client.brenno.discount', 'talvez nada', 0.35)],
            MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE,
            $this->now,
        );

        self::assertSame([], $records);
    }

    public function test_distilling_produces_a_memory_record_per_fact_above_the_floor(): void
    {
        $contextMemory = ContextMemory::start($this->tenantId, $this->workspaceId, $this->missionId, 'Manual', $this->now);
        $contextMemory->close($this->now);

        $records = $contextMemory->distill(
            [
                new DistilledFact('client.brenno.discount', 'Cliente sempre pede desconto', 0.97),
                new DistilledFact('client.brenno.payment-method', 'Cliente prefere PIX', 0.6),
                new DistilledFact('client.brenno.noise', 'ruído', 0.35),
            ],
            MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE,
            $this->now,
        );

        self::assertCount(2, $records);

        foreach ($records as $record) {
            self::assertInstanceOf(MemoryRecord::class, $record);
            self::assertSame('Manual', $record->origin());
            self::assertTrue($record->missionId()?->equals($this->missionId));
        }
    }
}
