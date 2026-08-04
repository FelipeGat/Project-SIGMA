# Release 5B — Mission Implementation

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md) e o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) ([ADR-0082](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)). **Revisão 1 — implementada.** Segunda metade da Release 5 — Mission Engine: a [Proposal 5A](0005a-mission-research.md) cobriu pesquisa e modelagem (sem código); esta cobre o `Domain/` real.

## Nota sobre o processo de aprovação desta Proposal

Diferente de toda Proposal anterior (3A, 4A), esta não passou por um segundo ciclo explícito de "Proposal escrita → aprovação → código" — e isso é sinalizado aqui deliberadamente, não escondido. A Proposal 5A já **listava o escopo exato de 5B** em sua seção "Escopo desta Proposal" ("Não existe ainda, fica para a Release 5B — Implementation: `packages/mission-engine/src/Domain/` — Value Objects, Enums, os Aggregates/Value Objects (`Mission`, `Subtask`, `Plan`, `ApprovalGate`, `RetryAttempt`, `Compensation`), os treze eventos como classes reais") e foi aprovada pelo Product Owner ("aprovado, siga"). A única pergunta que a 5A deixou explicitamente em aberto foi se 5B se divide em sub-Releases (Domain/Infrastructure, como 4A/4B) — **decisão tomada aqui: não** — 5B entrega só `Domain/` (mesmo escopo que 4A cobriu para o Memory Engine), então não há divisão adicional. Dado que o conteúdo já estava integralmente aprovado, a aprovação de "aprovado, siga" foi tratada como cobrindo também a Implementation do escopo já descrito — mesmo espírito de 3A/4A, cujas Proposals de código também não passaram por um segundo round-trip de aprovação depois de escritas. Sinalizado aqui para que o Product Owner possa objetar nesta revisão, se discordar da leitura.

## Objetivo

Implementar o `Domain/` do Mission Engine — `Mission` como Aggregate Root ([MISSION_MODEL.md](../../MISSION_MODEL.md)), carregando ciclo de vida, aprovação, retry, compensação e histórico — sem nenhuma dependência de persistência, banco ou infraestrutura, mesmo objetivo/disciplina de 3A/4A.

## Escopo

**Existe:**
- `packages/mission-engine/src/Domain/` (ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)).
- `Identifier` (cópia própria, mesmo precedente de `identity-engine`/`memory-engine` — [ADR-0063](../adr/0063-identificadores-como-value-objects.md)) e sete Value Objects concretos: `MissionId`, `SubtaskId`, `ApprovalGateId`, `CorrelationId` (identidade própria) e `TenantId`, `WorkspaceId`, `IntentId` (referências opacas a agregados de outros Engines).
- Seis enums: `MissionStatus`, `SubtaskStatus`, `ApprovalDecisionStatus`, `PlanSource`, `CompensationResult`, `ActorType`.
- Value Objects de suporte: `Actor`, `SubtaskCandidate`, `Plan`, `RetryAttempt`, `Compensation`, `MissionHistoryEntry` (novo, ver Decision Log).
- Entidades vivendo dentro do limite do aggregate `Mission` (nunca aggregates próprios, mesma convenção de `Session` no Identity Engine): `Subtask`, `ApprovalGate`.
- O aggregate `Mission` — `create()`/`reconstitute()` e os métodos de transição de todos os quatro fluxos de [MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md).
- `packages/mission-engine/src/Domain/Event/` — os treze eventos de [MISSION_EVENTS.md](../../MISSION_EVENTS.md), cada um `final`, implementando `DomainEvent`.
- `RecordsDomainEvents` — cópia própria, mesmo trait de Identity/Memory ([ADR-0062](../adr/0062-identity-nunca-conhece-outro-engine.md)).
- **37 testes automatizados** cobrindo `Subtask`, `ApprovalGate`, e o aggregate `Mission` ponta a ponta nos quatro fluxos (criação, aprovação/rejeição, execução/retry, falha com/sem efeito colateral, compensação com sucesso/falha, validação final com sucesso/reprovação, cancelamento, histórico).

**Nenhuma dependência de infraestrutura** — `composer.json` só declara `sigma/core`, mesmo padrão de 3A/4A.

**Não existe ainda:** `Application/`, `Infrastructure/`, `Interfaces/` do Mission Engine — persistência, consumo real de eventos, checagem de Permission, e a decisão de onde/como o Agent/Execution Engine (Releases 9/10) de fato disparam as transições de `Subtask` ficam para uma Release futura, sem sub-Release já nomeada (ver "Impacto" no Decision Log).

## Onde vive

`packages/mission-engine/src/Domain/` — única pasta nova desta Release.

## Dependências

- Release 5A — Mission Research, aprovada.
- Nenhuma dependência de código de nenhum outro Engine (ADR-0092) — verificado via Architecture Validation, ver Decision Log.

## Riscos

Os três já listados na Proposal 5A permanecem válidos; nenhum novo risco surgiu que não esteja já registrado no Decision Log como achado/decisão de Implementation.

## Entregáveis desta Proposal

- `packages/mission-engine/src/Domain/` completo (38 arquivos `.php`).
- `packages/mission-engine/tests/Domain/` (3 arquivos, 37 testes, 103 assertions).
- **Decision Log** ([0005b-mission-implementation-decision-log.md](0005b-mission-implementation-decision-log.md)).
- **Validation Report** ([0005b-mission-implementation-validation-report.md](0005b-mission-implementation-validation-report.md)).

## Critérios de Aceite

- `composer test` 100% verde em `packages/mission-engine`.
- Architecture Validation: `Domain/` importa só `sigma/core` e o próprio namespace `Sigma\MissionEngine\Domain` — nenhum import de `planner-engine`/`agent-engine`/`skill-engine`/`execution-engine`/`identity-engine`/`memory-engine`.
- Os treze eventos de [MISSION_EVENTS.md](../../MISSION_EVENTS.md) existem como classes reais, cada um produzido pela transição de domínio correta.
- `link-check`/`adr-check` limpos.
