# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 4A — Memory Domain: Proposal revisão 2, aguardando aprovação.** A revisão 1 do modelo foi aprovada nota 10/10 pelo Product Owner ("a decisão arquitetural mais importante desde o Envelope"), com uma evolução pedida antes do código — incorporada nesta rodada. Nenhum código do Memory Engine escrito ainda. Release 3.5 (Consolidation) e o commit `5596372` (Release 4A revisão 1) seguem completos e com push feito.

## O que existe (documentação)

- Tudo da Release 3.5, mais **[MEMORY_MODEL.md](../MEMORY_MODEL.md) revisão 2** — quatro entidades agora: `ContextMemory` (novo, estágio bruto/efêmero pré-Memory), `MemoryRecord` (com `confidence` e `origin` novos, `status` para contradição/retração), `KnowledgeRecord` (agora imutável/versionado — `version`/`previousVersionId`), `DigitalTwin` (agora estritamente Event-Driven, inclusive a primeira população).
- **[MEMORY_LIFECYCLE.md](../MEMORY_LIFECYCLE.md) revisão 2** — três fluxos: engajamento→destilação (`ContextMemory`→`MemoryRecord`), promoção gated por `confidence`, sincronização de Digital Twin sempre via evento.
- **[MEMORY_PROMOTION_RULES.md](../MEMORY_PROMOTION_RULES.md)** (novo) — exigência única do Product Owner antes do código; responde as oito perguntas de governança (sobe/desce/expira/vira Knowledge/vira Twin/quem promove/quem impede/quem revisa), com números concretos (pisos de `confidence`: 0.50/0.70/0.90; expiração: `ContextMemory` 30 dias, `MemoryRecord Operational` não-promovido 90 dias).
- **`contracts/Memory.contract.yaml`** atualizado — quatro tipos de output (`ContextMemory`/`MemoryRecord`/`KnowledgeRecord`/`DigitalTwin`), onze eventos, três Permissions novas no vocabulário (`memory.promote`/`memory.block_promotion`/`knowledge.curate`).
- **[DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md)/[EVENT_CATALOG.md](../EVENT_CATALOG.md)** — seção "Memory Engine" com onze eventos (cinco novos: `ContextMemoryStarted`/`ContextMemoryClosed`/`MemoryDeprecated`/`MemoryRetracted`/`MemorySubjectPinned`).
- **88 ADRs** — sete novas nesta rodada: 0082 (Processo Oficial de Desenvolvimento de Engines do SIGMA), 0083 (`ContextMemory`), 0084 (`confidence` como gate), 0085 (Digital Twin estritamente Event-Driven), 0086 (`KnowledgeRecord` imutável/versionado), 0087 (`origin` + candidatura a Knowledge), 0088 (retração/expiração/governança).
- **[CONTRIBUTING.md](../CONTRIBUTING.md)** — nova seção documentando o Processo Oficial de Desenvolvimento de Engines como obrigatório a partir da Release 4.
- Release 4A: [Proposal revisão 2](../docs/releases/0004a-memory-domain.md) — aguardando aprovação. Release 4B: [placeholder](../docs/releases/0004b-memory-infrastructure.md), sem mudança.

## O que existe (código)

Sem mudança em relação à Release 3.5 — **135 testes automatizados, todos passando**. Nenhum código de `packages/memory-engine` existe ainda.

## Decisões de fronteira resolvidas nesta rodada

- **Tensão real identificada e resolvida, não decidida em silêncio**: o pipeline `Conversation→ContextMemory→MemoryRecord→KnowledgeRecord→DigitalTwin` pedido pelo Product Owner, lido literalmente, sugeria promoção automática de Memory a Knowledge — conflitava com a curadoria humana já fixada. Resolvido como **candidatura**: `MemoryPromoted` (`toLevel: LongTerm`) sinaliza, nunca cria um `KnowledgeRecord` sozinho.
- **`confidence` é um gate independente da estrutura de repetição/generalização** — os dois precisam ser satisfeitos, nenhum substitui o outro.
- **"Quando uma Memory desce" exigiu um atributo novo** (`status: Active/Deprecated/Retracted`), não previsto na revisão 1 — contradição automática marca `Deprecated`; só ação humana explícita marca `Retracted`.
- **Digital Twin não tem mais um caminho de "primeira leitura direta"** — mesmo a primeira população passa a exigir um evento (para `Client`/`Project`/`Company`, a própria Capability de leitura publica um evento antes do Twin existir — desenho exato fica para a Release 8).

## Pendências / riscos sinalizados

- Mesmas da Release 3.5 (PHP 8.2, `autonomy_level_required` vs. `autonomyCapabilities`, `PermissionId` sem uso, migrations lazy, numeração Release 6/7).
- Pergunta em aberto na Proposal de 4A: `Identifier` deveria mover de `packages/identity-engine` para `packages/core` — recomendado, não decidido.
- Algoritmo de destilação de `ContextMemory` em `MemoryRecord` (cálculo de `confidence`) e algoritmo de detecção de contradição — ambos decisão de Implementation da Release 4B, não desta rodada.
- Três Permissions novas (`memory.promote`/`memory.block_promotion`/`knowledge.curate`) registradas no Contract e em ADR-0088, mas não implementadas — Identity Engine não muda nesta rodada.
- Componente estrutural **Scheduler** (ainda sem Release própria) continua sendo dependência da automação de promoção/refresh — Release 4A entrega a mecânica como operação explícita, não agendada.

## Bloqueios

**Aguardando aprovação da Proposal 4A revisão 2** (e de MEMORY_MODEL.md/MEMORY_LIFECYCLE.md/MEMORY_PROMOTION_RULES.md revisão 2, já incorporados) — nenhum código do Memory Engine antes disso. Push do commit desta rodada aguardando confirmação explícita, separada da confirmação já dada para `5596372`. Ver [NEXT.md](../memory/NEXT.md).
