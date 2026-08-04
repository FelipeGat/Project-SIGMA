<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Domain\ApprovalDecisionStatus;
use Sigma\MissionEngine\Domain\ApprovalGate;
use Sigma\MissionEngine\Domain\ApprovalGateId;

final class ApprovalGateTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_a_new_gate_is_pending(): void
    {
        $gate = new ApprovalGate(ApprovalGateId::generate(), 'autonomyCeiling insuficiente', $this->now);

        self::assertSame(ApprovalDecisionStatus::Pending, $gate->decision());
        self::assertNull($gate->decidedAt());
        self::assertNull($gate->decidedBy());
    }

    public function test_approve_records_decision(): void
    {
        $gate = new ApprovalGate(ApprovalGateId::generate(), 'autonomyCeiling insuficiente', $this->now);
        $decidedAt = new \DateTimeImmutable('2026-08-04T10:05:00+00:00');

        $gate->approve('felipe', $decidedAt);

        self::assertSame(ApprovalDecisionStatus::Approved, $gate->decision());
        self::assertSame('felipe', $gate->decidedBy());
        self::assertSame($decidedAt, $gate->decidedAt());
    }

    public function test_reject_records_decision(): void
    {
        $gate = new ApprovalGate(ApprovalGateId::generate(), 'autonomyCeiling insuficiente', $this->now);

        $gate->reject('felipe', $this->now);

        self::assertSame(ApprovalDecisionStatus::Rejected, $gate->decision());
    }

    public function test_a_decided_gate_cannot_be_decided_again(): void
    {
        $gate = new ApprovalGate(ApprovalGateId::generate(), 'autonomyCeiling insuficiente', $this->now);
        $gate->approve('felipe', $this->now);

        $this->expectException(SigmaException::class);
        $gate->reject('outro', $this->now);
    }
}
