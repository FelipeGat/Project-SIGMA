<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Domain\IdentityId;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class SessionTest extends TestCase
{
    public function test_a_freshly_started_session_has_no_workspace_selected(): void
    {
        $session = Session::start(IdentityId::generate(), new \DateTimeImmutable('2026-08-04T10:00:00+00:00'), new \DateInterval('PT1H'));

        self::assertFalse($session->hasWorkspaceSelected());
        self::assertNull($session->workspaceId());
    }

    public function test_is_expired_is_false_before_expires_at(): void
    {
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $session = Session::start(IdentityId::generate(), $now, new \DateInterval('PT1H'));

        self::assertFalse($session->isExpired($now->modify('+30 minutes')));
    }

    public function test_is_expired_is_true_after_expires_at(): void
    {
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $session = Session::start(IdentityId::generate(), $now, new \DateInterval('PT1H'));

        self::assertTrue($session->isExpired($now->modify('+2 hours')));
    }

    public function test_with_workspace_selected_returns_a_new_instance_without_mutating_the_original(): void
    {
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $session = Session::start(IdentityId::generate(), $now, new \DateInterval('PT1H'));
        $workspaceId = WorkspaceId::generate();

        $updated = $session->withWorkspaceSelected($workspaceId);

        self::assertFalse($session->hasWorkspaceSelected());
        self::assertTrue($updated->hasWorkspaceSelected());
        self::assertTrue($updated->workspaceId()->equals($workspaceId));
        self::assertSame($session->id()->toString(), $updated->id()->toString());
    }

    public function test_selecting_a_workspace_a_second_time_is_rejected(): void
    {
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $session = Session::start(IdentityId::generate(), $now, new \DateInterval('PT1H'))
            ->withWorkspaceSelected(WorkspaceId::generate());

        $this->expectException(SigmaException::class);

        $session->withWorkspaceSelected(WorkspaceId::generate());
    }
}
