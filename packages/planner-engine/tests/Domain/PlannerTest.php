<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\PlannerEngine\Domain\Actor;
use Sigma\PlannerEngine\Domain\ActorType;
use Sigma\PlannerEngine\Domain\CorrelationId;
use Sigma\PlannerEngine\Domain\Intent;
use Sigma\PlannerEngine\Domain\IntentId;
use Sigma\PlannerEngine\Domain\IntentKind;
use Sigma\PlannerEngine\Domain\Plan;
use Sigma\PlannerEngine\Domain\PlanSource;
use Sigma\PlannerEngine\Domain\PlanStep;
use Sigma\PlannerEngine\Domain\PlanTemplate;
use Sigma\PlannerEngine\Domain\PlanTemplateRegistry;
use Sigma\PlannerEngine\Domain\Planner;
use Sigma\PlannerEngine\Domain\PlanningFailure;
use Sigma\PlannerEngine\Domain\PlanningFailureReason;
use Sigma\PlannerEngine\Domain\TenantId;
use Sigma\PlannerEngine\Domain\WorkspaceId;

final class PlannerTest extends TestCase
{
    /** @param array<string, scalar> $parameters */
    private function intent(IntentKind $kind, array $parameters = []): Intent
    {
        return new Intent(
            IntentId::generate(),
            CorrelationId::generate(),
            TenantId::generate(),
            WorkspaceId::generate(),
            'O orçamento da Sea Master precisa refletir as decisões desta reunião',
            $kind,
            $parameters,
            new Actor(ActorType::User, 'user-1'),
            0,
        );
    }

    private function planner(): Planner
    {
        return new Planner(new PlanTemplateRegistry());
    }

    /** @return array<string, array<string, scalar>> */
    public static function completeParameters(): array
    {
        return [
            'nova_reuniao' => ['clientId' => 'c-1'],
            'novo_cliente' => ['razaoSocial' => 'Sea Master Ltda', 'produto' => 'AlfaControl'],
            'novo_orcamento' => ['clientId' => 'c-1', 'escopo' => '3 catracas'],
            'nova_obra' => ['orcamentoId' => 'o-1'],
            'nova_implantacao' => ['clientId' => 'c-1', 'sistema' => 'AlfaGym'],
            'nova_academia' => ['clientId' => 'c-1'],
            'novo_condominio' => ['clientId' => 'c-1'],
        ];
    }

    public function test_every_intent_kind_produces_a_non_empty_plan(): void
    {
        $params = self::completeParameters();

        foreach (IntentKind::cases() as $kind) {
            $outcome = $this->planner()->plan($this->intent($kind, $params[$kind->value]));

            self::assertInstanceOf(Plan::class, $outcome, sprintf('%s deveria produzir um Plan.', $kind->value));
            self::assertNotEmpty($outcome->subtaskCandidates);
            self::assertSame(PlanSource::Planner, $outcome->source);
        }
    }

    /**
     * Todo `PlanTemplate` precisa apontar para um Playbook que existe
     * de fato no repositório — o vínculo auditável entre a regra de
     * planejamento e o conhecimento de negócio que a justifica
     * (PLANNER_MANIFESTO.md).
     */
    public function test_every_template_points_to_a_playbook_that_exists(): void
    {
        $repoRoot = dirname(__DIR__, 4);

        foreach ((new PlanTemplateRegistry())->all() as $kind => $template) {
            self::assertFileExists(
                $repoRoot . '/' . $template->playbook,
                sprintf('O template de "%s" aponta para um Playbook inexistente.', $kind),
            );
        }
    }

    public function test_there_is_exactly_one_template_per_intent_kind(): void
    {
        $templates = (new PlanTemplateRegistry())->all();

        self::assertCount(count(IntentKind::cases()), $templates);
        foreach (IntentKind::cases() as $kind) {
            self::assertArrayHasKey($kind->value, $templates);
        }
    }

    public function test_missing_required_parameter_fails_without_producing_a_plan(): void
    {
        $outcome = $this->planner()->plan($this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1']));

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertSame(PlanningFailureReason::MissingRequiredParameter, $outcome->reason);
        self::assertStringContainsString('sistema', $outcome->detail);
        self::assertStringContainsString('playbooks/nova-implantacao.md', $outcome->detail);
    }

    public function test_a_blank_parameter_counts_as_missing(): void
    {
        $outcome = $this->planner()->plan($this->intent(IntentKind::NovaImplantacao, ['clientId' => 'c-1', 'sistema' => '   ']));

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertSame(PlanningFailureReason::MissingRequiredParameter, $outcome->reason);
    }

    public function test_an_empty_template_fails_instead_of_producing_a_degenerate_plan(): void
    {
        $registry = new PlanTemplateRegistry([
            new PlanTemplate(IntentKind::NovaObra, 'playbooks/nova-obra.md', [], []),
        ]);

        $outcome = (new Planner($registry))->plan($this->intent(IntentKind::NovaObra, ['orcamentoId' => 'o-1']));

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertSame(PlanningFailureReason::EmptyPlan, $outcome->reason);
    }

    /**
     * ADR-0097 e PLANNER_LIFECYCLE.md: as três causas são verificadas
     * em ordem, e a primeira encerra. Uma Intent cujo `kind` não tem
     * template **e** sem parâmetros reporta `UnknownIntentKind` — a
     * falha mais externa é a que dá a mensagem acionável.
     */
    public function test_unknown_kind_wins_over_missing_parameters(): void
    {
        // Registry sem nenhum template: nenhum `kind` é atendido.
        $registry = new PlanTemplateRegistry([]);

        // Intent sem nenhum parâmetro: as duas causas se aplicariam.
        $outcome = (new Planner($registry))->plan($this->intent(IntentKind::NovaImplantacao, []));

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertSame(PlanningFailureReason::UnknownIntentKind, $outcome->reason);
    }

    public function test_failure_preserves_the_intent_correlation_id(): void
    {
        $intent = $this->intent(IntentKind::NovaImplantacao, []);

        $outcome = $this->planner()->plan($intent);

        self::assertInstanceOf(PlanningFailure::class, $outcome);
        self::assertTrue($outcome->correlationId->equals($intent->correlationId));
        self::assertTrue($outcome->intentId->equals($intent->id));
    }

    /**
     * O Planner marca o nível; nunca compara contra o `autonomyCeiling`
     * nem cria gate — isso é do Mission Engine
     * (`Mission::advanceToNextSubtask()`, Release 5B).
     */
    public function test_the_plan_is_the_same_regardless_of_the_autonomy_ceiling(): void
    {
        $params = ['clientId' => 'c-1', 'sistema' => 'AlfaGym'];

        $low = $this->planner()->plan($this->intent(IntentKind::NovaImplantacao, $params));
        $high = $this->planner()->plan(new Intent(
            IntentId::generate(),
            CorrelationId::generate(),
            TenantId::generate(),
            WorkspaceId::generate(),
            'objetivo',
            IntentKind::NovaImplantacao,
            $params,
            new Actor(ActorType::User, 'user-1'),
            3,
        ));

        self::assertInstanceOf(Plan::class, $low);
        self::assertInstanceOf(Plan::class, $high);
        self::assertSame($low->toPayload(), $high->toPayload());
    }

    /**
     * Os "Pontos de decisão humana" dos Playbooks precisam sobreviver à
     * tradução: cada template tem ao menos um passo exigindo nível 3,
     * que é o que faz o Mission Engine abrir um `ApprovalGate` para
     * qualquer requisitante que não seja Operacional.
     */
    public function test_every_template_keeps_at_least_one_human_decision_point(): void
    {
        foreach ((new PlanTemplateRegistry())->all() as $kind => $template) {
            $levels = array_map(static fn (PlanStep $s): int => $s->requiredAutonomyLevel, $template->steps);

            self::assertContains(
                3,
                $levels,
                sprintf('O template de "%s" perdeu o ponto de decisão humana do Playbook.', $kind),
            );
        }
    }

    public function test_no_template_declares_a_capability_the_playbooks_do_not_name(): void
    {
        foreach ((new PlanTemplateRegistry())->all() as $kind => $template) {
            foreach ($template->steps as $step) {
                self::assertNull(
                    $step->candidateCapability,
                    sprintf('"%s" declara uma Capability — nenhum Playbook nomeia Capabilities (ADR-0027).', $kind),
                );
            }
        }
    }
}
