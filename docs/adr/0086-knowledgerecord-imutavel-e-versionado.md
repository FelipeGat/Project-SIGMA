# ADR-0086: KnowledgeRecord é imutável — atualização sempre cria uma nova versão, nunca edita em lugar

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[MEMORY_MODEL.md](../../MEMORY_MODEL.md) revisão 1 não definia o que acontece quando o arquivo de origem de um `KnowledgeRecord` em `/knowledge` é atualizado — implicitamente, o registro seria sobrescrito. O Product Owner, na revisão da Release 4A, pediu explicitamente que Knowledge seja versionado como Git — "Knowledge v1 → v2 → v3", nunca editado em lugar.

## Decisão

`KnowledgeRecord` ganha `version: int` (começa em `1`) e `previousVersionId` (referência opcional à versão anterior). Uma atualização do arquivo de origem em `/knowledge` cria um `KnowledgeRecord` novo, nunca sobrescreve o existente — o registro anterior permanece intacto e consultável indefinidamente. `sourcePath` (já existente desde a revisão 1) é a chave de lineage: todas as versões de um mesmo arquivo compartilham `sourcePath`; a versão corrente é a de maior `version` para o mesmo `sourcePath`+`tenantId`, sem precisar percorrer a cadeia de `previousVersionId` para encontrá-la.

## Consequências

- `KnowledgeRecord` nunca expira nem é apagado (ver [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md#quando-uma-memory-expira)) — toda versão histórica permanece consultável, mesmo quando substituída.
- O evento `KnowledgeRecordIndexed` já catalogado em [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md#memory-engine)/[EVENT_CATALOG.md](../../EVENT_CATALOG.md) passa a carregar `version` no payload — a atualização exata do payload acontece junto do código que o publica, na Implementation da Release 4B, mesma disciplina de todo evento do SIGMA.
- Volume de dados de `/knowledge` cresce de forma acumulativa (nunca decresce) — aceito conscientemente; Knowledge é, por natureza, um volume pequeno de arquivos curados manualmente, não um fluxo de alto volume como `MemoryRecord`.
- Não define o mecanismo de "diff" entre versões nem uma UI de histórico — Implementation/Interfaces, quando existirem.
