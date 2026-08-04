# ADR-0087: `MemoryRecord.origin` como string aberta; promoção a Knowledge é sempre candidatura, nunca automática

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Duas decisões distintas, tratadas juntas nesta ADR porque nasceram na mesma revisão e se relacionam pela mesma entidade (`MemoryRecord`):

1. O Product Owner pediu, na revisão da Release 4A, que todo `MemoryRecord` carregue de onde o fato chegou — exemplos citados: Telegram, GitHub, Meeting, Email, WhatsApp, Claude, ChatGPT, Gemini, Manual.
2. O pipeline que o Product Owner desenhou na mesma revisão (`Conversation → ContextMemory → MemoryRecord → KnowledgeRecord → DigitalTwin`), lido literalmente, sugere que um `MemoryRecord` `LongTerm` vira `KnowledgeRecord` automaticamente — o que conflita com uma decisão já estabelecida antes desta Release: Knowledge é sempre curadoria humana deliberada via `/knowledge` ([DOMAIN.md](../../DOMAIN.md#knowledge), [MEMORY_ARCHITECTURE.md](../../MEMORY_ARCHITECTURE.md)).

## Decisão

**Origin**: `MemoryRecord` ganha `origin: string` — modelado como string aberta, não como enum fechado em código. A lista citada pelo Product Owner é a lista de valores *conhecidos hoje*, documentada em [MEMORY_MODEL.md](../../MEMORY_MODEL.md#memoryrecord), não um conjunto fechado que exigiria alteração de código a cada nova integração. `ContextMemory` também carrega `origin`, copiado para o(s) `MemoryRecord` gerado(s) na destilação.

**Candidatura, não promoção automática a Knowledge**: quando um `MemoryRecord` atinge `LongTerm`, o Memory Engine publica o evento já catalogado `MemoryPromoted` (`toLevel: LongTerm`) — isso é o sinal de candidatura, nunca a criação nem a edição de um `KnowledgeRecord`. A transformação de candidato em `KnowledgeRecord` real continua exigindo uma ação humana explícita via `/knowledge`, revisada por um User com a Permission `knowledge.curate` (ver [MEMORY_PROMOTION_RULES.md — Quem revisa](../../MEMORY_PROMOTION_RULES.md#quem-revisa)).

## Consequências

- `origin` como string aberta evita que toda nova integração (um novo canal de mensageria, uma nova IA) exija uma mudança de código no `Domain/` do Memory Engine — o custo é perder a validação de enum em tempo de compilação; aceito conscientemente, mesmo trade-off já presente em outros pontos do SIGMA onde extensibilidade > rigidez.
- A resolução da candidatura preserva integralmente a decisão pré-existente de que Knowledge é curadoria humana — o pipeline do Product Owner é honrado no espírito (o SIGMA aponta o que aprendeu e parece durável) sem abrir uma exceção à regra de curadoria.
- Um `MemoryRecord` `Deprecated`/`Retracted` (ver [ADR-0088](0088-retracao-expiracao-e-governanca-de-promocao.md)) nunca é elegível como candidato a Knowledge, mesmo que tenha atingido `LongTerm` antes de ser contradito.
- O mecanismo de apresentação de um candidato a um humano (fila, notificação, tela) não é definido aqui — Implementation/Interfaces da Release 4B ou posterior.
