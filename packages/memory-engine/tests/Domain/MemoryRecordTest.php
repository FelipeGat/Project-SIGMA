<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\MemoryEngine\Domain\Event\MemoryDeprecated;
use Sigma\MemoryEngine\Domain\Event\MemoryPromoted;
use Sigma\MemoryEngine\Domain\Event\MemoryReactivated;
use Sigma\MemoryEngine\Domain\Event\MemoryRecordObserved;
use Sigma\MemoryEngine\Domain\Event\MemoryRetracted;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MemoryRecordId;
use Sigma\MemoryEngine\Domain\MemoryRecordStatus;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

/**
 * Cobre a mecânica de promoção de MEMORY_MODEL.md/MEMORY_PROMOTION_RULES.md
 * — os dois gates independentes (estrutura + confidence), nunca um
 * substituindo o outro, e nenhuma promoção direta Operational→LongTerm
 * (Critério de Aceite da Release 4A).
 */
final class MemoryRecordTest extends TestCase
{
    private TenantId $tenantId;
    private WorkspaceId $workspaceId;
    private MissionId $missionA;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->workspaceId = WorkspaceId::generate();
        $this->missionA = MissionId::generate();
        $this->now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');
    }

    public function test_observing_a_fact_creates_an_operational_record_and_records_the_event(): void
    {
        $record = MemoryRecord::observe(
            'client.brenno.discount-behavior',
            'Cliente sempre pede desconto',
            0.8,
            'WhatsApp',
            $this->tenantId,
            $this->workspaceId,
            $this->missionA,
            $this->now,
        );

        self::assertSame(MemoryLevel::Operational, $record->level());
        self::assertSame(MemoryRecordStatus::Active, $record->status());
        self::assertNull($record->promotedFrom());
        self::assertCount(1, $record->sourceMissionIds());

        $events = $record->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MemoryRecordObserved::class, $events[0]);
    }

    #[DataProvider('invalidConfidenceProvider')]
    public function test_observing_with_confidence_outside_zero_to_one_throws(float $confidence): void
    {
        $this->expectException(SigmaException::class);

        MemoryRecord::observe('x', 'y', $confidence, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
    }

    /** @return list<array{float}> */
    public static function invalidConfidenceProvider(): array
    {
        return [[-0.01], [1.01]];
    }

    public function test_observing_with_empty_subject_key_throws(): void
    {
        $this->expectException(SigmaException::class);

        MemoryRecord::observe('', 'content', 0.6, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
    }

    public function test_repetition_alone_without_enough_confidence_does_not_promote(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.60, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->pullDomainEvents();

        $promoted = $record->evaluatePromotionToProject([MissionId::generate()], $this->now);

        self::assertNull($promoted);
    }

    public function test_confidence_alone_without_repetition_does_not_promote(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->pullDomainEvents();

        $promoted = $record->evaluatePromotionToProject([], $this->now);

        self::assertNull($promoted);
    }

    public function test_repetition_and_confidence_together_promote_to_project(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->pullDomainEvents();

        $missionB = MissionId::generate();
        $promoted = $record->evaluatePromotionToProject([$missionB], $this->now);

        self::assertNotNull($promoted);
        self::assertSame(MemoryLevel::Project, $promoted->level());
        self::assertTrue($promoted->promotedFrom()?->equals($record->id()));
        self::assertCount(2, $promoted->sourceMissionIds());

        $events = $promoted->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MemoryPromoted::class, $events[0]);
        self::assertSame(MemoryLevel::Operational, $events[0]->fromLevel);
        self::assertSame(MemoryLevel::Project, $events[0]->toLevel);
    }

    public function test_promotion_never_deletes_or_mutates_the_source_record(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->pullDomainEvents();

        $record->evaluatePromotionToProject([MissionId::generate()], $this->now);

        self::assertSame(MemoryLevel::Operational, $record->level());
        self::assertSame(MemoryRecordStatus::Active, $record->status());
    }

    public function test_promoting_a_project_record_directly_to_long_term_requires_going_through_the_level(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);

        $this->expectException(SigmaException::class);
        $record->evaluatePromotionToLongTerm('client.*.x', [], $this->now);
    }

    public function test_project_to_long_term_requires_generalization_and_high_confidence(): void
    {
        $project = $this->promoteToProject(0.97);

        $noStructure = $project->evaluatePromotionToLongTerm('client.*.x', [], $this->now);
        self::assertNull($noStructure);

        $lowConfidenceProject = $this->promoteToProject(0.80);
        $withStructure = $lowConfidenceProject->evaluatePromotionToLongTerm('client.*.x', [WorkspaceId::generate()], $this->now);
        self::assertNull($withStructure);
    }

    public function test_project_to_long_term_promotes_and_drops_workspace_scope(): void
    {
        $project = $this->promoteToProject(0.97);
        $project->pullDomainEvents();

        $otherWorkspace = WorkspaceId::generate();
        $longTerm = $project->evaluatePromotionToLongTerm('client.*.discount-behavior', [$otherWorkspace], $this->now);

        self::assertNotNull($longTerm);
        self::assertSame(MemoryLevel::LongTerm, $longTerm->level());
        self::assertNull($longTerm->workspaceId());
        self::assertSame('client.*.discount-behavior', $longTerm->subjectKey());

        $events = $longTerm->pullDomainEvents();
        self::assertInstanceOf(MemoryPromoted::class, $events[0]);
        self::assertSame(MemoryLevel::LongTerm, $events[0]->toLevel);
    }

    public function test_contradiction_marks_active_record_as_deprecated_without_deleting_it(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.8, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->pullDomainEvents();
        $contradictor = MemoryRecord::observe('client.x', 'fato oposto', 0.8, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);

        $record->markContradicted($contradictor->id());

        self::assertSame(MemoryRecordStatus::Deprecated, $record->status());
        $events = $record->pullDomainEvents();
        self::assertInstanceOf(MemoryDeprecated::class, $events[0]);
    }

    public function test_a_deprecated_record_never_promotes_even_with_structure_and_confidence(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.97, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->markContradicted(MemoryRecord::observe('client.x', 'y', 0.9, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now)->id());

        $promoted = $record->evaluatePromotionToProject([MissionId::generate()], $this->now);

        self::assertNull($promoted);
    }

    public function test_a_deprecated_record_reactivates_and_a_retracted_one_never_does(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.8, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->markContradicted(MemoryRecordId::generate());
        $record->pullDomainEvents();

        $record->reactivate();
        self::assertSame(MemoryRecordStatus::Active, $record->status());
        self::assertInstanceOf(MemoryReactivated::class, $record->pullDomainEvents()[0]);

        $record->retract('user:felipe');
        self::assertSame(MemoryRecordStatus::Retracted, $record->status());

        $this->expectException(SigmaException::class);
        $record->reactivate();
    }

    public function test_retracting_an_already_retracted_record_throws(): void
    {
        $record = MemoryRecord::observe('client.x', 'fato', 0.8, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);
        $record->retract('user:felipe');

        $this->expectException(SigmaException::class);
        $record->retract('user:felipe');
    }

    private function promoteToProject(float $confidence): MemoryRecord
    {
        $record = MemoryRecord::observe('client.x', 'fato', $confidence, 'Manual', $this->tenantId, $this->workspaceId, $this->missionA, $this->now);

        return $record->evaluatePromotionToProject([MissionId::generate()], $this->now)
            ?? throw new \RuntimeException('Fixture inválida: era esperado promover a Project.');
    }
}
