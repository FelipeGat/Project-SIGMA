<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\MemoryEngine\Domain\ContextMemory;
use Sigma\MemoryEngine\Domain\DistilledFact;
use Sigma\MemoryEngine\Domain\Event\MemoryPromoted;
use Sigma\MemoryEngine\Domain\KnowledgeRecord;
use Sigma\MemoryEngine\Domain\MemoryLevel;
use Sigma\MemoryEngine\Domain\MemoryRecord;
use Sigma\MemoryEngine\Domain\MissionId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\WorkspaceId;

/**
 * Cobre o cenário ponta a ponta da Proposal 4A (Scenario Validation):
 * um engajamento completo — ContextMemory aberto → fechado → destilado
 * em MemoryRecord → promovido duas vezes → candidato a Knowledge
 * sinalizado, nunca convertido sozinho. Tudo em memória, sem nenhuma
 * infraestrutura.
 */
final class MemoryEngagementScenarioTest extends TestCase
{
    public function test_a_full_engagement_flows_from_raw_conversation_to_a_knowledge_candidate_signal(): void
    {
        $tenantId = TenantId::generate();
        $workspaceBrenno = WorkspaceId::generate();
        $workspaceNonaLu = WorkspaceId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        // Engajamento 1: Workspace do cliente Brenno, Mission A.
        $missionA = MissionId::generate();
        $engagementOne = ContextMemory::start($tenantId, $workspaceBrenno, $missionA, 'WhatsApp', $now);
        $engagementOne->appendContent('Cliente sempre pede desconto de 10% antes de fechar.');
        $engagementOne->close($now);
        $factsOne = [new DistilledFact('client.discount-behavior', 'Cliente sempre pede desconto antes de fechar', 0.97)];
        [$operationalInWorkspaceOne] = $engagementOne->distill($factsOne, MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE, $now);

        self::assertSame(MemoryLevel::Operational, $operationalInWorkspaceOne->level());

        // Engajamento 2: mesmo Workspace, Mission B — repetição dentro do Workspace.
        $missionB = MissionId::generate();
        $engagementTwo = ContextMemory::start($tenantId, $workspaceBrenno, $missionB, 'Meeting', $now);
        $engagementTwo->close($now);
        $factsTwo = [new DistilledFact('client.discount-behavior', 'De novo pediu desconto', 0.97)];
        [$reinforcingObservationInWorkspaceOne] = $engagementTwo->distill($factsTwo, MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE, $now);

        // A Application (4B) já resolveu que missionB também observou este subjectKey.
        $project = $operationalInWorkspaceOne->evaluatePromotionToProject(
            [$reinforcingObservationInWorkspaceOne->missionId()],
            $now,
        );

        self::assertNotNull($project);
        self::assertSame(MemoryLevel::Project, $project->level());
        self::assertTrue($project->promotedFrom()?->equals($operationalInWorkspaceOne->id()));

        // Engajamento 3: Workspace de outro cliente (Nona Lu) — mesma generalização de subjectKey.
        $missionC = MissionId::generate();
        $engagementThree = ContextMemory::start($tenantId, $workspaceNonaLu, $missionC, 'Email', $now);
        $engagementThree->close($now);
        $factsThree = [new DistilledFact('client.discount-behavior', 'Cliente da Nona Lu também sempre pede desconto', 0.95)];
        $engagementThree->distill($factsThree, MemoryRecord::MINIMUM_CONFIDENCE_TO_OBSERVE, $now);

        // A Application (4B) já resolveu que o mesmo padrão generalizado existe como Project em outro Workspace.
        $longTerm = $project->evaluatePromotionToLongTerm('client.*.discount-behavior', [$workspaceNonaLu], $now);

        self::assertNotNull($longTerm);
        self::assertSame(MemoryLevel::LongTerm, $longTerm->level());
        self::assertNull($longTerm->workspaceId(), 'LongTerm é cross-Workspace, por definição');

        $events = $longTerm->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MemoryPromoted::class, $events[0]);
        self::assertSame(MemoryLevel::LongTerm, $events[0]->toLevel, 'sinaliza candidatura a Knowledge — nunca cria o KnowledgeRecord sozinho');

        // Curadoria humana explícita, via /knowledge — ação separada, nunca disparada pelo evento acima.
        // (o próprio fato de precisar chamar KnowledgeRecord::index() manualmente aqui, fora de qualquer
        // listener do evento MemoryPromoted, é a prova de que a candidatura nunca cria o registro sozinha.)
        $knowledgeRecord = KnowledgeRecord::index(
            'clientes',
            'knowledge/clientes/comportamento-desconto.md',
            'Comportamento de desconto observado',
            $longTerm->content(),
            $tenantId,
        );

        self::assertSame(1, $knowledgeRecord->version());
    }

    public function test_a_memory_record_and_a_knowledge_record_about_the_same_subject_never_share_aggregate_identity(): void
    {
        $tenantId = TenantId::generate();
        $now = new \DateTimeImmutable('2026-08-04T10:00:00+00:00');

        $memoryRecord = MemoryRecord::observe(
            'client.brenno.discount-behavior',
            'Cliente sempre pede desconto',
            0.9,
            'Manual',
            $tenantId,
            WorkspaceId::generate(),
            MissionId::generate(),
            $now,
        );
        $knowledgeRecord = KnowledgeRecord::index('clientes', 'knowledge/clientes/brenno.md', 'Brenno', 'conteúdo', $tenantId);

        self::assertNotSame($memoryRecord->id()->toString(), $knowledgeRecord->id()->toString());
    }
}
