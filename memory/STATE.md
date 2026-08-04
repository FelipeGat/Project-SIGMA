# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 4A — Memory Domain: IMPLEMENTADA.** `packages/memory-engine/src/Domain/` completo — `ContextMemory`, `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin`, 35 testes automatizados, 100% passando. **Release 4B — Memory Infrastructure: Proposal revisão 1 apresentada, aguardando aprovação.** Achado real durante a escrita da Proposal: `RedisEventBus` nunca implementou entrega cross-processo de eventos (só local, mesmo processo) — sinalizado desde a Release 2/ADR-0057 como "fica para quando houver um consumidor de verdade". `UserTwin` sendo sincronizado a partir de `services/auth` é esse consumidor — a Proposal 4B passa a incluir, como escopo central, o primeiro listener Redis cross-processo real do projeto (`RedisSubscriber` + `services/memory-worker`).

## O que existe (documentação)

Tudo da rodada anterior (MEMORY_MODEL.md/MEMORY_LIFECYCLE.md revisão 2, MEMORY_PROMOTION_RULES.md, ADRs 0082-0088), mais:

- **[Proposal 4A](../docs/releases/0004a-memory-domain.md)** marcada como aprovada e implementada.
- **[Decision Log 4A](../docs/releases/0004a-memory-domain-decision-log.md)** — decisões locais da Implementation, incluindo por que `Identifier` não foi movida para `packages/core` nesta rodada, e o achado real do evento `MemoryReactivated` faltante.
- **[Validation Report 4A](../docs/releases/0004a-memory-domain-validation-report.md)** — 35 testes, 103 assertions, 100% passando; suíte completa do monorepo (170 testes) revalidada.
- **`DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`/`contracts/Memory.contract.yaml`** — evento `MemoryReactivated` (`memory.reactivated`) acrescentado durante a Implementation (doze eventos agora, não onze).
- **[Proposal 4B](../docs/releases/0004b-memory-infrastructure.md)** (novo, revisão 1) — substitui o placeholder anterior; escopo agora inclui `RedisSubscriber` (listener Redis cross-processo real, primeiro do projeto) e `services/memory-worker`, além de `Application/Infrastructure/Interfaces` do Memory Engine. Nenhuma API HTTP pública nesta Release — sem consumidor real ainda.

## O que existe (código)

- **`packages/memory-engine/src/Domain/`** (novo) — `Identifier` (cópia própria, não compartilhada com Identity Engine) + 7 Value Objects de identificador (`ContextMemoryId`/`MemoryRecordId`/`KnowledgeRecordId`/`DigitalTwinId`/`TenantId`/`WorkspaceId`/`MissionId`); 4 enums; `DistilledFact` (Value Object de suporte); os quatro Aggregates (`ContextMemory`, `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin`); `RecordsDomainEvents`; 12 classes de evento em `Domain/Event/`.
- **170 testes automatizados no monorepo** (135 anteriores + 35 novos do memory-engine), todos passando — `core` 8, `kernel` 36, `event-bus` 6, `gateway` 8, `identity-engine` 72 (10 skipped, infra indisponível), `auth` 5 (5 skipped), `memory-engine` 35.
- Nenhum código de `Application/`/`Infrastructure/`/`Interfaces/` do Memory Engine ainda — escopo da Release 4B.

## Decisões de Implementation desta rodada

- **`Identifier` permanece duplicada** entre `identity-engine` e `memory-engine` — a Proposal recomendava consolidar em `packages/core`, mas a aprovação desta rodada não endereçou a pergunta explicitamente; escolhida a opção de menor risco (não tocar no Identity Engine já validado). Consolidação segue recomendada, sinalizada em NEXT.md.
- **`TenantId`/`WorkspaceId`/`MissionId` são referências opacas próprias do Memory Engine**, nunca os tipos do Identity Engine — bounded context cunha seu próprio identificador para referência cross-Engine, mesmo com o mesmo valor de string.
- **`ContextMemory::distill()`/`MemoryRecord::evaluatePromotion*()` recebem dados já resolvidos** (`DistilledFact`, listas de Missions/Workspaces reforçando) — o algoritmo real (destilação, detecção de contradição, generalização de `subjectKey`) é decisão de Implementation da Release 4B, não desta.
- **`MemorySubjectPinned` não tem Aggregate próprio** — ação de governança sem estado de domínio associado; produzido diretamente pela Application 4B, não por um método de `Domain/`.
- **Achado real**: faltava o evento `MemoryReactivated` para "Deprecated volta a Active" — catalogado antes do código, dentro da própria Implementation, quando o gap apareceu.

## Pendências / riscos sinalizados

- Mesmas de sempre (PHP 8.2, `autonomy_level_required` vs. `autonomyCapabilities`, `PermissionId` sem uso, migrations lazy, numeração Release 6/7).
- `Identifier` duplicada — consolidação em `packages/core` recomendada, não decidida.
- `MemorySubjectPinned` sem lugar de persistência definido — decisão da Release 4B.
- Algoritmos de destilação/contradição/generalização — Implementation da Release 4B.

## Bloqueios

**Aguardando aprovação da Proposal 4B** — nenhum código de `Application/Infrastructure/Interfaces` do Memory Engine antes disso. Push do(s) commit(s) desta rodada aguardando confirmação explícita (mesma regra de sempre). Ver [NEXT.md](../memory/NEXT.md).
