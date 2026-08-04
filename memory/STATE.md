# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 4A — Memory Domain: Proposal apresentada, aguardando aprovação.** Modelo completo do Memory Engine ([MEMORY_MODEL.md](../MEMORY_MODEL.md), [MEMORY_LIFECYCLE.md](../MEMORY_LIFECYCLE.md)) publicado, seguindo a recomendação explícita do Product Owner de investir tempo extra na modelagem — Release 4 é o "segundo marco mais importante do projeto". Nenhum código do Memory Engine escrito ainda. Release 3.5 (Consolidation) segue completa e com push feito.

## O que existe (documentação)

- Tudo da Release 3.5, mais: **[MEMORY_MODEL.md](../MEMORY_MODEL.md)** (novo) — `MemoryRecord`/`KnowledgeRecord`/`DigitalTwin`, mecânica de promoção entre níveis, fronteira com Identity Engine (UserTwin) e com a Release 16 (Knowledge simples vs. semântico).
- **[MEMORY_LIFECYCLE.md](../MEMORY_LIFECYCLE.md)** (novo) — fluxo de observação/promoção de Memory + sincronização de Digital Twin.
- **`contracts/Memory.contract.yaml`** (novo) — contrato antes do código, mesmo padrão de `Identity.contract.yaml`.
- **[DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md)/[EVENT_CATALOG.md](../EVENT_CATALOG.md)** — seção "Memory Engine" nova, seis eventos catalogados antes do código.
- **81 ADRs** — novas: 0079 (UserTwin desde a Release 4), 0080 (Knowledge simples nesta Release, semântico fica para a 16), 0081 (mecânica de promoção — repetição/generalização por `subjectKey`).
- Release 4A: [Proposal](../docs/releases/0004a-memory-domain.md) — aguardando aprovação. Release 4B: [placeholder](../docs/releases/0004b-memory-infrastructure.md).
- Correções de nomenclatura obsoleta: "Épico E5"/"Camada L5" → "Release 4" em `knowledge/README.md` e `packages/memory-engine/README.md`.

## O que existe (código)

Sem mudança em relação à Release 3.5 — **135 testes automatizados, todos passando**. Nenhum código de `packages/memory-engine` existe ainda.

## Decisões de fronteira resolvidas nesta rodada

- **`UserTwin` populado desde a Release 4** (não espera a Release 8) — os eventos do Identity Engine já são reais hoje. `ClientTwin`/`ProjectTwin`/`CompanyTwin` ficam com schema pronto, sem instância até a Release 8.
- **Knowledge da Release 4 é índice simples** (busca textual sobre `/knowledge`) — busca semântica/embeddings fica para a Release 16.
- **Mecânica de promoção formalizada**: `subjectKey` estável por registro; repetição (mesmo Workspace, Missions diferentes) promove Operational→Project; generalização (Workspaces diferentes) promove Project→LongTerm; `promotedFrom` preserva proveniência, nunca apaga o original.

## Pendências / riscos sinalizados

- Mesmas da Release 3.5 (PHP 8.2, `autonomy_level_required` vs. `autonomyCapabilities`, `PermissionId` sem uso, migrations lazy, numeração Release 6/7).
- Pergunta em aberto na Proposal de 4A: `Identifier` (base de Value Object de identificador) deveria mover de `packages/identity-engine` para `packages/core`, já que Memory Engine precisa do mesmo mecanismo — recomendado, não decidido.
- Componente estrutural **Scheduler** (ainda sem Release própria) é dependência da automação de promoção/refresh de Twin — Release 4A entrega a mecânica como operação explícita, não agendada.
- Cache Layer segue sem reivindicação — não claimado pela Release 4.

## Bloqueios

**Aguardando aprovação da Proposal da Release 4A** — nenhum código do Memory Engine antes disso. Ver [NEXT.md](../memory/NEXT.md).
