<?php

declare(strict_types=1);

namespace Sigma\PlannerEngine\Domain;

/**
 * Os sete `PlanTemplate` do SIGMA — um por Playbook existente.
 *
 * REGRA QUE GOVERNA ESTE ARQUIVO: **um `PlanStep` só existe se o
 * Playbook correspondente o descreve.** Onde o Playbook é vago, o
 * template tem menos passos — nunca passos inventados para parecer
 * completo. Um Planner que preenche lacunas de conhecimento de negócio
 * com suposições plausíveis decide errado com confiança, que é
 * exatamente o que ADR-0097 combate.
 *
 * As descrições são deliberadamente próximas da redação do Playbook,
 * para que a divergência entre os dois seja visível numa leitura lado
 * a lado.
 *
 * `requiredAutonomyLevel` vem dos "Pontos de decisão humana" de cada
 * Playbook, nunca de julgamento próprio: um passo que o Playbook marca
 * como exigindo validação humana recebe `3`, o que faz o Mission
 * Engine abrir um `ApprovalGate` para qualquer requisitante que não
 * seja Operacional (ADR-0029). Onde o Playbook não diz, usa-se o nível
 * mais restritivo cabível.
 *
 * `candidateCapability` é `null` em todos os passos: **nenhum Playbook
 * nomeia Capabilities** — eles nomeiam Skills, que não são a mesma
 * coisa (ADR-0027), e o Capability Registry é a Release 18. Preencher
 * este campo aqui seria inventar. `candidateAgent` só é preenchido
 * onde a própria fase do Playbook nomeia um Agent.
 */
final class PlanTemplateRegistry
{
    /** @var array<string, PlanTemplate>|null */
    private ?array $templates;

    /**
     * @param list<PlanTemplate>|null $templates Só para teste — em
     *        produção é sempre `null`, e valem os sete templates
     *        construídos aqui. Injetável para que um teste possa
     *        exercitar casos que os sete templates reais, por serem
     *        corretos, nunca produzem (um `kind` sem template, um
     *        template vazio) sem precisar afrouxar esta classe.
     */
    public function __construct(?array $templates = null)
    {
        $this->templates = $templates === null ? null : $this->indexByKind($templates);
    }

    public function find(IntentKind $kind): ?PlanTemplate
    {
        return $this->all()[$kind->value] ?? null;
    }

    /** @return array<string, PlanTemplate> */
    public function all(): array
    {
        return $this->templates ??= $this->build();
    }

    /** @return array<string, PlanTemplate> */
    private function build(): array
    {
        $templates = [
            new PlanTemplate(
                IntentKind::NovaReuniao,
                'playbooks/nova-reuniao.md',
                ['clientId'],
                [
                    // "registrar uma reunião" é o exemplo literal de nível 2
                    // (Delegado) em ADR-0029.
                    new PlanStep('Confirmar horário/link da reunião, se ainda não agendada', 2),
                    new PlanStep('Reunir contexto prévio relevante (Knowledge do cliente, Missions/orçamentos em aberto)', 0),
                    new PlanStep('Acompanhar/registrar a reunião', 2),
                    new PlanStep('Consolidar em Document (ata)', 2, 'manus'),
                    // "Propor próximos passos ... para aprovação humana" — o
                    // próprio Playbook põe o gate nesta fase.
                    new PlanStep('Propor próximos passos para aprovação humana', 3),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovoCliente,
                'playbooks/novo-cliente.md',
                ['razaoSocial', 'produto'],
                [
                    // "Criação de cadastro definitivo no Gestor.Alfa ... exige
                    // confirmação humana."
                    new PlanStep('Confirmar/criar o cadastro do cliente no sistema de origem, buscando antes de criar para evitar duplicidade', 3),
                    new PlanStep('Registrar contexto qualitativo inicial em Knowledge (decisor, origem do lead, particularidades)', 2),
                    new PlanStep('Identificar, a partir do produto de interesse, se este cliente segue para Novo Orçamento e/ou Nova Implantação', 0),
                    // Notificação interna ao Team — o gate do Playbook é sobre
                    // comunicação enviada ao cliente, não a esta.
                    new PlanStep('Notificar o Team comercial responsável', 2),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovoOrcamento,
                'playbooks/novo-orcamento.md',
                ['clientId', 'escopo'],
                [
                    new PlanStep('Reunir escopo e validar contra o catálogo e itens comerciais reais', 1),
                    new PlanStep('Estruturar a proposta com itens desmembrados, aplicando a política de precificação vigente', 2),
                    new PlanStep('Gerar peça visual da proposta, quando aplicável', 2, 'gemini'),
                    new PlanStep('Vincular a um lead no Funil de Vendas, se ainda não vinculado', 2),
                    // "Toda proposta é revisada por um humano antes de ser
                    // enviada ao cliente — este Playbook nunca termina em envio
                    // automático."
                    new PlanStep('Submeter para aprovação humana antes do envio ao cliente', 3),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovaObra,
                'playbooks/nova-obra.md',
                ['orcamentoId'],
                [
                    // "Abertura formal da obra ... exige confirmação humana."
                    new PlanStep('Criar a obra a partir do orçamento aprovado, com etapas e peso/% correspondentes', 3),
                    new PlanStep('Agendar marcos relevantes (início, vistorias)', 2),
                    new PlanStep('Notificar Team técnico responsável', 2),
                    // O próprio Playbook declara esta fase como "ponto de
                    // integração com Missions subsequentes, não coberto em
                    // detalhe" — mantida porque é uma fase listada, com a
                    // redação do Playbook, sem detalhamento inventado.
                    new PlanStep('Acompanhar ao longo da execução: fotos (antes/andamento/depois) e ocorrências', 2),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovaImplantacao,
                'playbooks/nova-implantacao.md',
                ['clientId', 'sistema'],
                [
                    new PlanStep('Confirmar escopo contratado a partir do orçamento de origem', 1),
                    // "Ativação em produção do ambiente do cliente exige
                    // validação humana explícita."
                    new PlanStep('Provisionar/configurar o ambiente do cliente no sistema contratado', 3),
                    new PlanStep('Agendar treinamento/onboarding com o cliente', 2),
                    new PlanStep('Acompanhar até o critério de "implantado" e a transição para operação/suporte contínuo', 2),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovaAcademia,
                'playbooks/nova-academia.md',
                ['clientId'],
                [
                    new PlanStep('Provisionar ambiente da academia no AlfaGym (empresa/tenant)', 2),
                    // "Migração de base de alunos e ativação em produção exigem
                    // validação humana explícita — dado real de aluno em jogo."
                    new PlanStep('Migrar/cadastrar base de alunos e planos, quando aplicável', 3, 'claude'),
                    new PlanStep('Configurar integrações específicas (catraca, cobrança) conforme contratado', 2, 'claude'),
                    new PlanStep('Treinamento da equipe da academia', 2),
                    new PlanStep('Transição para operação — suporte contínuo conforme contrato', 3),
                ],
            ),
            new PlanTemplate(
                IntentKind::NovoCondominio,
                'playbooks/novo-condominio.md',
                ['clientId'],
                [
                    new PlanStep('Provisionar ambiente do condomínio no AlfaControl (empresa/tenant)', 2),
                    // "Migração de base de moradores e ativação em produção
                    // exigem validação humana explícita — dado real de morador
                    // em jogo."
                    new PlanStep('Cadastrar estrutura de blocos/unidades e migrar base de moradores, quando aplicável', 3, 'claude'),
                    new PlanStep('Configurar perfis de acesso conforme contratado', 2, 'claude'),
                    new PlanStep('Treinamento do síndico/administração', 2),
                    new PlanStep('Transição para operação — suporte contínuo conforme contrato', 3),
                ],
            ),
        ];

        return $this->indexByKind($templates);
    }

    /**
     * @param list<PlanTemplate> $templates
     * @return array<string, PlanTemplate>
     */
    private function indexByKind(array $templates): array
    {
        $byKind = [];
        foreach ($templates as $template) {
            $byKind[$template->kind->value] = $template;
        }

        return $byKind;
    }
}
