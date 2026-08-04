# ADR-0088: Retração, expiração e governança de promoção de Memory

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O Product Owner exigiu, antes de qualquer código da Release 4, um documento respondendo oito perguntas de governança sobre o ciclo de vida de uma Memory — "quando sobe? quando desce? quando expira? quando vira Knowledge? quando vira Twin? quem pode promover? quem pode impedir? quem revisa?". As perguntas de subida/Knowledge/Twin já tinham resposta em [ADR-0081](0081-mecanica-de-promocao-de-memory.md), [ADR-0084](0084-confidence-como-gate-de-promocao.md) e [ADR-0087](0087-memoryrecord-origin-e-candidatura-a-knowledge.md). Descida, expiração, e os três papéis de governança (promover/impedir/revisar) eram lacunas reais — nenhum documento anterior definia o que acontece quando um `MemoryRecord` já promovido é contradito, por quanto tempo cada entidade vive, ou quem tem autoridade para intervir manualmente.

## Decisão

Ver [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md) para a resposta completa e os números exatos; esta ADR fixa os mecanismos estruturais:

1. **`status` em `MemoryRecord`**: `Active` \| `Deprecated` \| `Retracted`. Um registro nunca é rebaixado de nível em lugar — a escada Operational/Project/LongTerm só cresce criando registros novos ([ADR-0081](0081-mecanica-de-promocao-de-memory.md)). "Descer" é uma mudança de `status`, não de `level`.
2. **Contradição automática**: uma nova observação com `confidence` acima do piso mínimo, mesmo `subjectKey`, conteúdo contraditório a um `MemoryRecord` `Active` → o registro existente vira `Deprecated` automaticamente, evento `MemoryDeprecated` publicado. Nunca apagado.
3. **Retração**: só por ação humana explícita (Permission `memory.block_promotion`) — vira `Retracted`, excluído de consultas padrão e de evidência para futuras promoções, mas nunca apagado.
4. **Expiração**: políticas diferentes por entidade — `ContextMemory` bruto expira em 30 dias após fechamento (padrão, configurável por Tenant); `MemoryRecord` `Operational` nunca promovido expira em 90 dias sem reforço; `Project`/`LongTerm` não expiram por tempo, só por contradição; `KnowledgeRecord` nunca expira (é versionado, [ADR-0086](0086-knowledgerecord-imutavel-e-versionado.md)); `DigitalTwin` nunca expira, fica `stale`.
5. **Papéis**: promoção é automática por padrão (`EvaluatePromotion`); um User com `memory.promote` pode forçar; um User com `memory.block_promotion` pode fixar um `subjectKey` contra promoção ou retratar um registro; o Audit Engine revisa automaticamente todo evento de Memory; um User com `knowledge.curate` revisa toda candidatura a Knowledge.

## Consequências

- Fecha as oito perguntas de governança exigidas pelo Product Owner antes do código — nenhuma delas fica implícita ou assumida.
- Introduz três novas Permissions no vocabulário do Identity Engine (`memory.promote`, `memory.block_promotion`, `knowledge.curate`), seguindo o formato já fixado em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md#permission) — nenhuma delas é criada como código nesta Release (Identity Engine não muda nesta rodada); ficam registradas aqui como vocabulário esperado para quando a Application/Interfaces do Memory Engine (4B) precisar checá-las contra o Context resolvido pelo Identity Engine.
- Introduz o evento novo `MemoryDeprecated` (e, implicitamente, o registro de eventos de intervenção humana — fixação e retração) — a serem catalogados em [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md#memory-engine)/[EVENT_CATALOG.md](../../EVENT_CATALOG.md) nesta mesma rodada, antes do código, mesma disciplina de sempre.
- Os números concretos (30/90 dias, pisos de `confidence`) são parâmetros de configuração, não constantes de código — mudar o padrão global exige uma ADR nova, não uma edição silenciosa de [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md).
- O algoritmo de detecção de contradição entre dois `MemoryRecord` do mesmo `subjectKey` não é definido aqui — decisão de Implementation da Release 4B.
