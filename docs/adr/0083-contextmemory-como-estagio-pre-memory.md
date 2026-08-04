# ADR-0083: ContextMemory como estágio bruto e efêmero, antes de qualquer MemoryRecord

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[MEMORY_MODEL.md](../../MEMORY_MODEL.md) revisão 1 modelava `MemoryRecord` como nascendo diretamente de "um fato observado numa Mission", sem nenhuma entidade intermediária entre a Conversation bruta e o primeiro `MemoryRecord` `Operational`. O Product Owner, na revisão da Release 4A, propôs explicitamente um quarto elo — `Conversation → ContextMemory → MemoryRecord → KnowledgeRecord → DigitalTwin` — argumentando que o SIGMA precisa de um lugar para reter o que está sendo dito/observado *durante* um engajamento, antes de qualquer julgamento sobre o que vale a pena virar Memory.

## Decisão

`ContextMemory` é uma quarta entidade do Memory Engine, custodiada junto de `MemoryRecord`/`KnowledgeRecord`/`DigitalTwin`, mas com natureza distinta das três: bruta e efêmera, nunca curada, nunca consultada por outro Engine como fonte de fato. Carrega `tenantId`/`workspaceId`/`missionId` (opcional)/`origin`/`rawContent`/`startedAt`/`endedAt`/`status` (`Active`\|`Closed`). Não participa da escada Operational/Project/LongTerm — não é promovida, é **destilada**: no fechamento (`status: Closed`), o Memory Engine avalia `rawContent` e produz zero ou mais `MemoryRecord` `Operational`, cada um com seu próprio `confidence` atribuído no momento da destilação (ver [ADR-0084](0084-confidence-como-gate-de-promocao.md)).

Ver [MEMORY_MODEL.md — ContextMemory](../../MEMORY_MODEL.md#contextmemory) para o modelo completo e [MEMORY_LIFECYCLE.md — Fluxo 1](../../MEMORY_LIFECYCLE.md#fluxo-1--do-engajamento-ao-memoryrecord) para o fluxo.

## Consequências

- Separa duas responsabilidades que antes estavam implícitas em "um fato observado numa Mission": captura bruta (`ContextMemory`) e julgamento do que é digno de virar fato experiencial (destilação → `MemoryRecord`). Isso permite reter contexto rico durante um engajamento sem comprometer `MemoryRecord` com ruído.
- `ContextMemory` tem sua própria política de expiração, mais curta que a de `MemoryRecord` (ver [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md#quando-uma-memory-expira)) — custo de retenção bruta é assumido conscientemente por um período limitado, não indefinidamente.
- A destilação em si (o algoritmo que decide o que extrair de `rawContent`) não é definida por esta ADR nem por [MEMORY_MODEL.md](../../MEMORY_MODEL.md) — é decisão de Implementation da Release 4B, quando `Application/Infrastructure` do Memory Engine existirem.
- `ContextMemory` não é consultável por outro Engine como fonte de verdade — só os três aggregates já existentes na revisão 1 continuam nesse papel. Isso evita que `ContextMemory` vire, na prática, um quarto tipo de "fato" concorrendo com `MemoryRecord`.
