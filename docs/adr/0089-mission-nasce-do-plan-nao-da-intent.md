# ADR-0089: O domínio de Mission começa quando um Plan é aceito, não quando uma Intent é recebida

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

`ARCHITECTURE.md §6` descreve um `stateDiagram-v2` de "ciclo de vida da Mission" que começa em `Recebida` e passa por `Interpretando`/`Planejando`/`Rejeitada` antes de a Mission propriamente existir (`SubtarefasCriadas`). Esses três primeiros estágios são, na prática, responsabilidade do Kernel (porta de entrada), do Intent Engine (Release 7) e do Planner Engine (Release 6) — nenhum deles é código do Mission Engine (Release 5). Escrever `MISSION_MODEL.md`/`MISSION_LIFECYCLE.md` exigia decidir explicitamente onde o Aggregate Root `Mission` de fato começa a existir, para não modelar como "estado da Mission" algo que pertence a outro Engine.

## Decisão

O Aggregate Root `Mission` só passa a existir quando um `Plan` já está pronto e é aceito pelo Mission Engine — nunca antes. `MissionRequested`, `IntentDetected`, `IntentRejected` e `MissionPlanned` continuam existindo como eventos Technical ([EVENT_MODEL.md](../../EVENT_MODEL.md)), publicados por Kernel/Intent Engine/Planner Engine, consumidos (quando existirem) pelo Mission Engine — nunca produzidos por ele, e nunca representando um `MissionStatus`. O primeiro estado real de uma `Mission`, em [MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md), é `Created`.

## Consequências

- `ARCHITECTURE.md §6` não é reescrito (ilustra o fluxo fim-a-fim, cross-Engine, papel que continua útil) — mas deixa de ser a fonte de verdade do ciclo de vida *do Aggregate Mission* especificamente; essa fonte passa a ser [MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md).
- `Mission Engine` (Release 5) é construído e testado contra um `Plan` manual/mockado, nunca contra uma Intent — reforça, para o domínio de Mission especificamente, a Ordem de Desenvolvimento já fixada em [ADR-0031](0031-ordem-runtime-vs-desenvolvimento.md).
- `ADR-0003` mantém a referência a "`ARCHITECTURE.md §5`" para o ciclo de vida, que hoje é `§6` — divergência de referência já existente antes desta ADR, não corrigida retroativamente no texto da ADR-0003 (ADRs não são revogadas por edição), sinalizada aqui e em `memory/NEXT.md`.
