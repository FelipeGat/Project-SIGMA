<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Application;

use PHPUnit\Framework\TestCase;
use Sigma\Kernel\InMemoryEventBus;
use Sigma\MemoryEngine\Application\UseCase\CheckDigitalTwinStaleness;
use Sigma\MemoryEngine\Application\UseCase\ProjectDigitalTwinFromEvent;
use Sigma\MemoryEngine\Domain\TwinSubjectType;
use Sigma\MemoryEngine\Tests\Application\Fake\InMemoryDigitalTwinRepository;

/**
 * Cobre o handler que MemoryEngineModule regista via
 * IEventBus::subscribe() — o único caminho de criação/atualização de
 * DigitalTwin (ADR-0085). `externalRef` é `identityId`, não `userId`
 * (refinamento do Decision Log da Release 4B).
 */
final class DigitalTwinProjectionTest extends TestCase
{
    public function test_identity_created_projects_a_new_user_twin(): void
    {
        $repository = new InMemoryDigitalTwinRepository();
        $handler = new ProjectDigitalTwinFromEvent($repository, new InMemoryEventBus());
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $handler->handle('identity.created', [
            'identityId' => 'identity-123',
            'userId' => 'user-456',
            'tenantId' => 'tenant-789',
        ], $now);

        $twin = $repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'identity-123');
        self::assertNotNull($twin);
        self::assertSame('user-456', $twin->state()['userId']);
    }

    public function test_workspace_selected_updates_the_twin_already_created_by_identity_created(): void
    {
        $repository = new InMemoryDigitalTwinRepository();
        $handler = new ProjectDigitalTwinFromEvent($repository, new InMemoryEventBus());
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $handler->handle('identity.created', ['identityId' => 'identity-123', 'userId' => 'user-456', 'tenantId' => 'tenant-789'], $now);
        $handler->handle('workspace.selected', ['identityId' => 'identity-123', 'workspaceId' => 'workspace-999'], $now);

        $twin = $repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'identity-123');
        self::assertSame('workspace-999', $twin?->state()['workspaceId']);
        self::assertSame('user-456', $twin?->state()['userId'], 'workspace.selected nunca apaga o que identity.created já sabia');
    }

    public function test_workspace_selected_arriving_out_of_order_is_ignored_not_fatal(): void
    {
        $repository = new InMemoryDigitalTwinRepository();
        $handler = new ProjectDigitalTwinFromEvent($repository, new InMemoryEventBus());

        $handler->handle('workspace.selected', ['identityId' => 'identity-never-seen', 'workspaceId' => 'workspace-999'], new \DateTimeImmutable());

        self::assertNull($repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'identity-never-seen'));
    }

    public function test_an_unrelated_event_is_ignored(): void
    {
        $repository = new InMemoryDigitalTwinRepository();
        $handler = new ProjectDigitalTwinFromEvent($repository, new InMemoryEventBus());

        $handler->handle('budget.created_via_gestor', ['anything' => 'here'], new \DateTimeImmutable());

        self::assertNull($repository->findBySubjectTypeAndExternalRef(TwinSubjectType::User, 'anything'));
    }

    public function test_checking_staleness_publishes_the_stale_event(): void
    {
        $repository = new InMemoryDigitalTwinRepository();
        $eventBus = new InMemoryEventBus();
        $handler = new ProjectDigitalTwinFromEvent($repository, $eventBus);
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
        $handler->handle('identity.created', ['identityId' => 'identity-123', 'userId' => 'user-456', 'tenantId' => 'tenant-789'], $now);

        $staleFired = false;
        $eventBus->subscribe('digital_twin.stale', function () use (&$staleFired): void {
            $staleFired = true;
        });

        $isStale = (new CheckDigitalTwinStaleness($repository, $eventBus))->execute(
            TwinSubjectType::User,
            'identity-123',
            $now->modify('+2 hours'),
            new \DateInterval('PT1H'),
        );

        self::assertTrue($isStale);
        self::assertTrue($staleFired);
    }
}
