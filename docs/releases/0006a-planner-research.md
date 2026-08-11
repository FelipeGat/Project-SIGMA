# Release 6A — Planner Research

Primeira etapa do [Processo Oficial de Desenvolvimento de Engines](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) para o Planner Engine: **Research** — o levantamento do que o repositório já afirma sobre o domínio, feito antes de qualquer modelagem, no mesmo formato da [Release 5A](0005a-mission-research.md).

Este documento **não decide nada**. Ele reúne o que já foi decidido, aponta as contradições encontradas, e lista as perguntas que `PLANNER_MODEL.md` vai precisar responder. As decisões vêm depois, com aprovação explícita.

## Superfície pesquisada

177 menções a "Planner" em 68 arquivos (`.md`/`.yaml`, excluindo `vendor/` e Decision Logs de Releases anteriores). Os que de fato definem o domínio:

| Fonte | O que estabelece |
|---|---|
| [ADR-0012](../adr/0012-planner-decide-nunca-a-ia.md) | O Planner decide o Plan — **nunca** a IA. A ADR mais importante do domínio. |
| [ADR-0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md) | O Planner publica `MissionPlanned`; o Mission Engine só existe depois disso. |
| [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md) | `Plan` é Value Object do Mission Engine; o Planner publica **a mesma forma de dado** via evento, nunca via import. |
| [ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md) | O Planner é construído contra Intents **manuais**, antes de o Intent Engine existir. |
| [ADR-0029](../adr/0029-autonomia-progressiva.md) | Autonomia Progressiva — o Planner resolve `requiredAutonomyLevel` por Subtask candidata. |
| [ADR-0037](../adr/0037-declarativo-nao-imperativo.md) | O usuário descreve estado desejado; **o Planner é quem decide o "como"**. |
| [ARCHITECTURE.md §3/§5](../architecture/ARCHITECTURE.md) | Cadeia Intent → Planner → Mission; contexto "Planejamento" possui `Plan` e `Subtask` (candidatos). |
| [EVENT_MODEL.md](../../EVENT_MODEL.md) | `MissionPlanned` (`mission.planned`) já catalogado como evento Technical do Planner. |
| [playbooks/](../../playbooks/) | Sete Playbooks — o conhecimento documentado de *como o Planner deveria pensar*. |
| [SGL.md](../../SGL.md) | A SIGMA Language, candidata natural a representação de Plan. |

## O que já está decidido (não se rediscute)

1. **O Planner decide, a IA não.** Se o Planner usar IA como apoio, o resultado passa por validação e regras do próprio Planner — nunca é repassado cegamente (ADR-0012).
2. **O Planner publica `MissionPlanned`; não cria Mission.** O Mission Engine reage a esse evento (ADR-0089).
3. **Nenhuma dependência de código com `intent-engine` nem com `mission-engine`.** O acoplamento é por forma de dado, via evento (ADR-0031, ADR-0092).
4. **A entrada é uma Intent estruturada manualmente** nesta Release — o Intent Engine só chega na 7.
5. **O Planner resolve `requiredAutonomyLevel`** de cada Subtask candidata; o Mission Engine apenas compara contra o `autonomyCeiling` da Mission (ADR-0029, já implementado em `Mission::advanceToNextSubtask()`).

## Achados reais desta pesquisa

### 1. `packages/planner-engine/README.md` afirmava uma dependência que a ADR-0092 já havia proibido

A ADR-0092 registra, em suas consequências, que a dependência incorreta `planner-engine → intent-engine` foi "corrigida na mesma rodada". Foi — **em `packages/README.md` apenas**. O `README.md` do próprio pacote seguiu dizendo *"depende de `packages/intent-engine`"* até 2026-08-11.

Mesma classe de bug que a ADR corrigiu, num arquivo que ela não olhou. Corrigido nesta rodada.

### 2. A ADR-0092 deixou uma pergunta explicitamente em aberto para este momento

> *"Quando o Planner Engine (Release 6) for modelado, seu próprio `PLANNER_MODEL.md` decide se ele reaproveita o Value Object `Plan` de `packages/mission-engine` (via `packages/core`, movido lá) ou mantém sua própria representação interna que só se torna o `Plan` do Mission Engine no momento de publicar `mission.planned`."*

É a decisão de arquitetura central da Release 6A. Ver Pergunta 1 abaixo.

### 3. Os sete Playbooks são o requisito funcional real, e nenhum está completo

Todos seguem o template (Gatilho, Contexto necessário, Fases esperadas, Agentes/Skills, Pontos de decisão humana, Critérios de sucesso). O de Nova Implantação é o mais desenvolvido e já exibe as quatro capacidades que o Planner precisará ter:

- **Fases ordenadas** → Subtasks candidatas em sequência
- **"Playbook especializado aplicável, quando existir"** → composição/delegação entre Playbooks
- **"Ativação em produção exige validação humana explícita"** → `requiredAutonomyLevel` alto, gerando `ApprovalGate`
- **"Contexto necessário"** → o Planner precisa consultar Knowledge/Memory *antes* de conseguir planejar

O quarto item é o mais pesado: significa que planejar **não é uma função pura da Intent**. O Planner pode precisar de dados que só uma Skill traz — e o Skill Engine é a Release 8.

### 4. `MissionPlanned` está catalogado, mas sem payload especificado

`EVENT_MODEL.md` lista o evento e seu canal (`mission.planned`); `EVENT_CATALOG.md` não detalha payload nem contrato. Não existe `contracts/Planner.contract.yaml`. A Release 6A precisa produzi-los — mesma lacuna que a 5A encontrou e fechou para Mission.

### 5. `packages/planner-engine/README.md` promete "primeira versão apoiada nos Playbooks já documentados"

É a única afirmação no repositório sobre *como* o Planner planeja na primeira versão. Não há ADR sustentando-a. Ver Pergunta 2.

## Perguntas que `PLANNER_MODEL.md` precisa responder

**1. De quem é o `Plan`?** Três caminhos possíveis, com consequências distintas:
   - (a) mover `Plan`/`SubtaskCandidate` para `packages/core`, compartilhado — resolve também a duplicação de `Identifier`, já sinalizada há três Releases;
   - (b) o Planner mantém representação interna própria e serializa para a forma do Mission Engine ao publicar;
   - (c) o Planner produz apenas o payload do evento, sem Value Object próprio.

**2. Como o Planner planeja na primeira versão?** Playbooks como dados estruturados (exigiria convertê-los de Markdown para algo legível por código), regras codificadas em PHP, ou uma tabela de Plans-template no banco? A promessa do README aponta para Playbooks, sem dizer em que forma.

**3. O Planner tem estado persistido?** Um `Plan` produzido é guardado pelo Planner, ou ele é sem estado — recebe Intent, publica `MissionPlanned`, esquece? Isso decide se a Release 6 tem `Infrastructure/` com banco ou não, e é a maior variável de tamanho da Release.

**4. Uma Intent gera quantas Missions?** [ADR-0028](../adr/0028-intencao-nao-comando.md) e o `README.md` afirmam que uma Intent **pode decompor em múltiplas Missions**. Nenhum documento diz quem decide isso. Se for o Planner, `MissionPlanned` é publicado N vezes — e o modelo precisa de um conceito acima do `Plan` para agrupá-las.

**5. O Planner consulta Memory/Knowledge?** O item "Contexto necessário" dos Playbooks sugere que sim. Mas o Memory Engine não tem API pública (decisão consciente da 4B, "sem consumidor real"). Se o Planner for esse consumidor, a Release 6 pode ter que abrir essa API — escopo que precisa ser explicitado, não descoberto no meio da implementação.

**6. O que acontece quando o Planner não consegue planejar?** Não existe evento catalogado para "Intent recebida, plano impossível". `IntentRejected` pertence ao Intent Engine e significa outra coisa (não entendi o pedido), não esta (entendi, mas não sei como fazer). Provável evento novo — mesma disciplina de `MissionFailed` na 5B e `MemoryReactivated` na 4A, catalogado antes do código.

## Próximo passo

Com estas seis perguntas respondidas pelo Product Owner, a Release 6A produz, na ordem de ADR-0082: `PLANNER_MANIFESTO.md` → `PLANNER_MODEL.md` → `PLANNER_LIFECYCLE.md` → seção nova em `DOMAIN_EVENTS.md`/`EVENT_CATALOG.md` → `contracts/Planner.contract.yaml` → as ADRs correspondentes. Só então a Proposal da Release 6B (Implementation).

As perguntas 3, 4 e 5 são as que mais mudam o **tamanho** da Release, e por isso deveriam ser decididas antes das demais.
