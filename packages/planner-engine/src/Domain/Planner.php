<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

/**
 * O único lugar do sistema onde se decide o que uma Mission vai fazer
 * (ADR-0012). Serviço de domínio, não entidade: não tem identidade,
 * não tem estado, não guarda nada (ADR-0095).
 *
 * Implementa o fluxo único de PLANNER_LIFECYCLE.md. Os três pontos de
 * falha são verificados **em ordem**, e o primeiro que dispara encerra
 * — uma Intent de `kind` desconhecido com parâmetros faltando reporta
 * `UnknownIntentKind`, nunca `MissingRequiredParameter`. Reportar a
 * falha mais externa é o que dá uma mensagem acionável a quem for
 * diagnosticar.
 *
 * Não há fallback genérico: quando nenhum template atende, o Planner
 * recusa-se a planejar (ADR-0097).
 */
final class Planner
{
    public function __construct(private readonly PlanTemplateRegistry $templates)
    {
    }

    /**
     * Produz exatamente um dos dois — nunca ambos, nunca nenhum.
     */
    public function plan(Intent $intent): Plan|PlanningFailure
    {
        // 1. Resolver o PlanTemplate.
        $template = $this->templates->find($intent->kind);
        if ($template === null) {
            return $this->fail(
                $intent,
                PlanningFailureReason::UnknownIntentKind,
                sprintf(
                    'Nenhum PlanTemplate atende o kind "%s". Provavelmente falta um Playbook que descreva este tipo de Mission.',
                    $intent->kind->value,
                ),
            );
        }

        // 2. Validar os parâmetros obrigatórios. O Planner não busca
        //    contexto em lugar nenhum nesta Release — não consulta
        //    Memory, não consulta Knowledge, não chama Skill. O que o
        //    template exige precisa vir na própria Intent.
        $missing = $template->missingParameters($intent);
        if ($missing !== []) {
            return $this->fail(
                $intent,
                PlanningFailureReason::MissingRequiredParameter,
                sprintf(
                    'A Intent não trouxe: %s. Exigidos por %s.',
                    implode(', ', $missing),
                    $template->playbook,
                ),
            );
        }

        // 3. Traduzir cada PlanStep em SubtaskCandidate. Um template
        //    sem passos é erro de configuração — precisa falhar
        //    visivelmente, nunca produzir um Plan degenerado.
        if ($template->steps === []) {
            return $this->fail(
                $intent,
                PlanningFailureReason::EmptyPlan,
                sprintf('O PlanTemplate de "%s" não tem nenhum passo (%s).', $intent->kind->value, $template->playbook),
            );
        }

        // 4. Montar o Plan. Quem publica é a Application — o Domain
        //    decide, nunca fala com infraestrutura.
        return $template->toPlan();
    }

    private function fail(Intent $intent, PlanningFailureReason $reason, string $detail): PlanningFailure
    {
        return new PlanningFailure($intent->id, $intent->correlationId, $reason, $detail);
    }
}
