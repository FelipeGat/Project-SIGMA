<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinCreated;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinStale;
use Sigma\MemoryEngine\Domain\Event\DigitalTwinUpdated;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

/** Cobre ADR-0085 — Digital Twin é estritamente Event-Driven. */
final class DigitalTwinTest extends TestCase
{
    private TenantId $tenantId;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_projecting_creates_the_twin_and_records_the_created_event(): void
    {
        $twin = DigitalTwin::project(TwinSubjectType::User, 'identity-123', ['name' => 'Felipe'], $this->tenantId, $this->now);

        self::assertSame(TwinSubjectType::User, $twin->subjectType());
        self::assertSame(['name' => 'Felipe'], $twin->state());
        self::assertSame($this->now, $twin->lastSyncedAt());

        $events = $twin->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DigitalTwinCreated::class, $events[0]);
    }

    public function test_applying_a_projection_updates_state_and_records_the_updated_event(): void
    {
        $twin = DigitalTwin::project(TwinSubjectType::User, 'identity-123', ['name' => 'Felipe'], $this->tenantId, $this->now);
        $twin->pullDomainEvents();

        $syncedAt = $this->now->modify('+1 hour');
        $twin->applyProjection(['name' => 'Felipe', 'workspace' => 'Cliente Brenno'], $syncedAt);

        self::assertSame(['name' => 'Felipe', 'workspace' => 'Cliente Brenno'], $twin->state());
        self::assertSame($syncedAt, $twin->lastSyncedAt());

        $events = $twin->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DigitalTwinUpdated::class, $events[0]);
    }

    public function test_checking_staleness_within_the_window_returns_false_and_records_nothing(): void
    {
        $twin = DigitalTwin::project(TwinSubjectType::User, 'identity-123', [], $this->tenantId, $this->now);
        $twin->pullDomainEvents();

        $isStale = $twin->checkStaleness($this->now->modify('+30 minutes'), new \DateInterval('PT1H'));

        self::assertFalse($isStale);
        self::assertSame([], $twin->pullDomainEvents());
    }

    public function test_checking_staleness_outside_the_window_returns_true_and_records_the_stale_event(): void
    {
        $twin = DigitalTwin::project(TwinSubjectType::User, 'identity-123', [], $this->tenantId, $this->now);
        $twin->pullDomainEvents();

        $isStale = $twin->checkStaleness($this->now->modify('+2 hours'), new \DateInterval('PT1H'));

        self::assertTrue($isStale);
        $events = $twin->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(DigitalTwinStale::class, $events[0]);
    }
}
