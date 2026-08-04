# ADR-0084: `confidence` como gate independente de promoção, além da estrutura de repetição/generalização

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[ADR-0081](0081-mecanica-de-promocao-de-memory.md) definiu a promoção de `MemoryRecord` só em termos estruturais — repetição de `subjectKey` no mesmo Workspace, generalização entre Workspaces. Não havia nenhuma noção de "quão confiável" um fato é — dois registros com a mesma estrutura de repetição promoviam igualmente, mesmo que um viesse de uma fonte muito mais confiável que outro. O Product Owner, na revisão da Release 4A, pediu explicitamente um atributo `confidence`, com o exemplo "`0.35` não promove, `0.97` promove".

## Decisão

Todo `MemoryRecord` carrega `confidence: float` (`0.0`–`1.0`). A promoção passa a exigir **dois gates independentes**, ambos satisfeitos: a condição estrutural já definida em [ADR-0081](0081-mecanica-de-promocao-de-memory.md) **e** um piso mínimo de `confidence` para cada salto — `0.50` para um `MemoryRecord` sequer ser criado, `0.70` para `Operational → Project`, `0.90` para `Project → LongTerm` (valores completos e justificativa em [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md#quando-uma-memory-sobe)). Estrutura sem confiança suficiente não promove; confiança alta sem repetição/generalização também não promove — nenhum dos dois gates substitui o outro.

`confidence` não decai automaticamente por tempo nesta Release (dependeria do componente estrutural Scheduler) — só é recalculado quando `EvaluatePromotion` roda de novo sobre o mesmo `subjectKey`, ou quando uma observação contraditória chega (ver [ADR-0088](0088-retracao-expiracao-e-governanca-de-promocao.md)).

## Consequências

- A mecânica de [ADR-0081](0081-mecanica-de-promocao-de-memory.md) continua válida sem alteração — esta ADR adiciona um segundo gate, não substitui o primeiro.
- O algoritmo que calcula `confidence` no momento da destilação de um `ContextMemory` (ver [ADR-0083](0083-contextmemory-como-estagio-pre-memory.md)) não é definido aqui — é decisão de Implementation da Release 4B, provável heurística nesta Release, sem exigir um modelo de linguagem dedicado.
- Introduz um novo tipo de configuração por Tenant (os pisos de `confidence`) — mesmo mecanismo de configuração já usado em outros pontos do SIGMA, não um precedente novo de arquitetura.
- Um `MemoryRecord` que nunca atinge o piso de promoção permanece `Operational` indefinidamente até expirar (ver [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md#quando-uma-memory-expira)) — não é um erro, é o comportamento esperado para um fato que se repete mas nunca ganha confiança suficiente.
