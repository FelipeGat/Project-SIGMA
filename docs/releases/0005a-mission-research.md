# Release 5A — Mission Research

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md) e o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) ([ADR-0082](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)). **Revisão 1 — aguardando aprovação do Product Owner.** Diferente de toda Proposal anterior, esta não propõe código nenhum — propõe **aprovar o bundle de pesquisa e modelagem completo** (Manifesto, Model, Lifecycle, Events, Contract, cinco ADRs de direção) como pré-requisito para a Release 5B — Implementation, exatamente a sequência que o Product Owner pediu explicitamente ao revisar a Release 4.5: "5A — Mission Research (sem código) → Review do CTO → 5B — Implementation → Review → Push."

## Objetivo

Modelar completamente o domínio de Mission — a primeira Engine que faz o SIGMA decidir, não só guardar/autenticar/sincronizar (ver [MISSION_MANIFESTO.md](../../MISSION_MANIFESTO.md)) — antes de qualquer código, com o mesmo cuidado já investido em Identity (Release 3) e Memory (Release 4), e a exigência adicional explícita do Product Owner: `Mission` como Aggregate Root carregando ciclo de vida, eventos, estado, histórico, autonomia, **aprovação, retries, compensações**, correlação.

## O que foi produzido nesta rodada (o que esta Proposal pede para aprovar)

- **[MISSION_MANIFESTO.md](../../MISSION_MANIFESTO.md)** — por que Mission é diferente de tudo que o SIGMA já construiu; o que já estava decidido (ADR-0003/0028/0031) e o que esta rodada acrescenta.
- **[MISSION_MODEL.md](../../MISSION_MODEL.md)** — as entidades (`Mission`, `Subtask`, `Plan`, `ApprovalGate`, `RetryAttempt`, `Compensation`), a fronteira explícita entre o que é responsabilidade do Mission Engine e o que pertence a Intent/Planner/Agent/Skill/Execution Engine (nenhum dos quais existe ainda).
- **[MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md)** — quatro fluxos (criação/aprovação inicial, execução/retry/novos gates, compensação, validação/conclusão), reconciliando três descrições pré-existentes e parciais do mesmo fluxo (`ADR-0003`, `ARCHITECTURE.md §6`, `EVENT_MODEL.md`).
- **[MISSION_EVENTS.md](../../MISSION_EVENTS.md)** — doze eventos Technical (três já catalogados desde a Foundation, sem dono real até agora; nove novos formalizando aprovação/retry/compensação), refletidos em [EVENT_MODEL.md](../../EVENT_MODEL.md) e [EVENT_CATALOG.md](../../EVENT_CATALOG.md#mission-engine).
- **`contracts/Mission.contract.yaml`** — o contrato antes do código, mesmo padrão de Identity/Memory.
- **Cinco ADRs de direção** ([0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md)–[0093](../adr/0093-mission-workspace-opcional.md))**:** onde o domínio de Mission começa (não na Intent); aprovação como estado de primeira classe; retry é histórico de Subtask, compensação é estado da Mission; `Plan`/Subtask candidata são conceito próprio do Mission Engine, sem dependência de código em `planner-engine`; `Mission.workspaceId` é opcional.
- **Duas correções de documentação, achado real desta pesquisa**: `packages/README.md` e `packages/mission-engine/README.md` listavam incorretamente `mission-engine` dependendo de `planner-engine` (e `planner-engine` de `intent-engine`) — contradizia diretamente [ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md). Corrigido nesta rodada, ver Decision Log.

## Escopo desta Proposal (o que está sendo pedido para aprovar agora)

**Existe, para aprovação:** os sete artefatos acima — nenhuma linha de código.

**Não existe ainda, fica para a Release 5B — Implementation:**
- `packages/mission-engine/src/Domain/` — Value Objects, Enums, os Aggregates/Value Objects (`Mission`, `Subtask`, `Plan`, `ApprovalGate`, `RetryAttempt`, `Compensation`), os doze eventos como classes reais.
- Se a Release 5B se divide em sub-Releases (Domain/Infrastructure, mesmo padrão de [ADR-0060](../adr/0060-release-dividida-em-sub-releases.md) já usado em Identity/Memory) — decisão a tomar quando a Proposal de 5B for escrita, não antecipada aqui.
- Qualquer Application/Infrastructure/Interfaces, persistência, consumo real de eventos.

## Arquitetura

Nenhuma mudança de código nesta rodada — a "arquitetura" desta Proposal é a decisão de fronteira já formalizada nos cinco ADRs: Mission nasce do `Plan`, nunca da Intent ([ADR-0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md)); `packages/mission-engine` depende só de `core`/`kernel`, nunca de `planner-engine` ([ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md)) — consistente com a Ordem de Desenvolvimento já decidida em [ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md).

## Dependências

- Release 4.5 — Platform Validation, completa.
- Nenhuma dependência de código de nenhum outro Engine (deliberado, ver ADR-0092).

## Riscos

1. **Mission é um domínio genuinamente mais complexo que Identity/Memory** — o primeiro a modelar estado com aprovação humana, retry e compensação simultaneamente. Mitigado pela extensão do processo de pesquisa (relatório dedicado antes de qualquer texto de modelo) e pela reconciliação explícita das três descrições pré-existentes, em vez de inventar um quarto vocabulário isolado.
2. **A fronteira "Mission nasce do Plan, não da Intent" pode se revelar difícil de manter limpa na Implementation** (ex: tentação de a Application do Mission Engine já antecipar lógica de interpretação de Intent) — mitigado por Architecture Validation explícita na 5B verificando que `Domain/` do Mission Engine nunca importa nada de Intent/Planner.
3. **Aprovação/retry/compensação, sem nenhum consumidor real ainda (Agent/Execution Engine não existem)**, corre o risco de ficar abstrata demais para validar de verdade — mitigado por Scenario Validation da 5B usando execução/falha/aprovação simuladas manualmente (mesmo espírito do `Plan` mockado), provando a máquina de estados mesmo sem os Engines vizinhos existirem.

## Entregáveis desta Proposal

- Os sete artefatos já listados em "O que foi produzido nesta rodada" — já publicados no repositório nesta mesma rodada.
- **Decision Log** (`docs/releases/0005a-mission-research-decision-log.md`).
- Esta Proposal, revisão 1, aguardando aprovação — sem Validation Report (não há execução de código para validar nesta sub-Release).

## Critérios de Aceite

- Os sete artefatos existem, estão consistentes entre si (nenhuma contradição não sinalizada), e cada decisão de direção real está registrada como ADR, não decidida silenciosamente no corpo dos documentos de modelo.
- As duas correções de documentação (`packages/README.md`/`packages/mission-engine/README.md`) aplicadas.
- `link-check`/`adr-check` limpos (0 links quebrados, 0 ADRs referenciadas inexistentes).
- Aprovação explícita do Product Owner antes de qualquer código de `packages/mission-engine/src/` — sem exceção, mesma disciplina de toda Release anterior.
