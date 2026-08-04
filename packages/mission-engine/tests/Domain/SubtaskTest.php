<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MissionEngine\Domain\Subtask;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\SubtaskId;
use Sigma\MissionEngine\Domain\SubtaskStatus;

final class SubtaskTest extends TestCase
{
    private function makeSubtask(): Subtask
    {
        $candidate = new SubtaskCandidate('Enviar orçamento', 'agent.sales', 'capability.quote.send', 0);

        return Subtask::fromCandidate(SubtaskId::generate(), $candidate);
    }

    public function test_a_new_subtask_starts_pending(): void
    {
        $subtask = $this->makeSubtask();

        self::assertSame(SubtaskStatus::Pending, $subtask->status());
        self::assertSame([], $subtask->retryAttempts());
        self::assertNull($subtask->result());
    }

    public function test_happy_path_assign_execute_validate(): void
    {
        $subtask = $this->makeSubtask();

        $subtask->assign();
        self::assertSame(SubtaskStatus::Assigned, $subtask->status());

        $subtask->startExecution();
        self::assertSame(SubtaskStatus::Executing, $subtask->status());

        $subtask->validate(['sent' => true]);
        self::assertSame(SubtaskStatus::Validated, $subtask->status());
        self::assertSame(['sent' => true], $subtask->result());
    }

    public function test_retry_keeps_executing_and_accumulates_history(): void
    {
        $subtask = $this->makeSubtask();
        $subtask->assign();
        $subtask->startExecution();

        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $first = $subtask->retry('timeout', $now);
        $second = $subtask->retry('timeout de novo', $now);

        self::assertSame(SubtaskStatus::Executing, $subtask->status());
        self::assertSame(1, $first->attemptNumber);
        self::assertSame(2, $second->attemptNumber);
        self::assertCount(2, $subtask->retryAttempts());
    }

    public function test_fail_requires_executing(): void
    {
        $subtask = $this->makeSubtask();

        $this->expectException(SigmaException::class);
        $this->expectExceptionCode(0);
        $subtask->fail();
    }

    public function test_compensate_is_allowed_from_failed(): void
    {
        $subtask = $this->makeSubtask();
        $subtask->assign();
        $subtask->startExecution();
        $subtask->fail();

        $subtask->compensate();

        self::assertSame(SubtaskStatus::Compensated, $subtask->status());
    }

    public function test_compensate_is_also_allowed_from_validated(): void
    {
        // Achado real da Implementation: uma validação final da Mission pode
        // reprovar uma Subtask que já tinha validado normalmente — ver
        // Mission::failValidation() e o Decision Log da Release 5B.
        $subtask = $this->makeSubtask();
        $subtask->assign();
        $subtask->startExecution();
        $subtask->validate('ok');

        $subtask->compensate();

        self::assertSame(SubtaskStatus::Compensated, $subtask->status());
    }

    public function test_compensate_is_not_allowed_from_pending(): void
    {
        $subtask = $this->makeSubtask();

        $this->expectException(SigmaException::class);
        $subtask->compensate();
    }

    public function test_skip_requires_pending(): void
    {
        $subtask = $this->makeSubtask();
        $subtask->assign();

        $this->expectException(SigmaException::class);
        $subtask->skip();
    }
}
