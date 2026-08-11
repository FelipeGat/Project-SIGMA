<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

/**
 * Enum FECHADO, um valor por Playbook existente — e é justamente por
 * ser fechado que `PlanningFailureReason::UnknownIntentKind` consegue
 * ser um sinal preciso em vez de um palpite (ADR-0097). Um `kind` que
 * o Planner não conhece falha visivelmente; nunca cai num template
 * genérico.
 *
 * Um Playbook novo exige um valor novo aqui — acoplamento deliberado
 * entre a documentação de negócio e o código.
 */
enum IntentKind: string
{
    case NovaReuniao = 'nova_reuniao';
    case NovoCliente = 'novo_cliente';
    case NovoOrcamento = 'novo_orcamento';
    case NovaObra = 'nova_obra';
    case NovaImplantacao = 'nova_implantacao';
    case NovaAcademia = 'nova_academia';
    case NovoCondominio = 'novo_condominio';
}
