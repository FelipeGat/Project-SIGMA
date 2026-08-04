# ADR-0092: `Plan`/`Subtask` candidata são conceitos do próprio Mission Engine — nenhuma dependência de código em `planner-engine`

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

`packages/README.md` e `packages/mission-engine/README.md` listavam `mission-engine` como dependente de `packages/planner-engine` — achado real ao pesquisar para esta Release (ver relatório de pesquisa da Release 5A). Isso contradiz diretamente [ADR-0031](0031-ordem-runtime-vs-desenvolvimento.md): Mission (Release 5) é construída e testada **antes** de Planner (Release 6) existir, contra um `Plan` mockado/manual. Se `mission-engine` de fato dependesse do pacote Composer `planner-engine`, a Release 5 não poderia ser implementada até a Release 6 estar pronta — o oposto do que o roadmap decide.

## Decisão

`Plan` e a forma "candidata" de `Subtask` (ver [MISSION_MODEL.md](../../MISSION_MODEL.md#plan)) são Value Objects do próprio `packages/mission-engine` — nunca importados de `packages/planner-engine`. O campo `Plan.source` (`Planner` \| `Manual`) já modela a diferença entre um Plan real (Release 6+) e um Plan mockado (Release 5) sem exigir dependência de código nenhuma — quando o Planner Engine existir, ele publica um `Plan` na mesma forma de dado já modelada aqui, via evento (`mission.planned`, Technical), nunca via chamada direta ou import de classe.

`packages/README.md` e `packages/mission-engine/README.md` corrigidos nesta mesma rodada, removendo a dependência incorreta.

## Consequências

- Reforça, para o par Mission/Planner, o mesmo padrão que [ADR-0031](0031-ordem-runtime-vs-desenvolvimento.md) já estabeleceu como princípio geral — cada Engine é construído contra a **forma de dado** que a Ordem de Runtime exige dele, nunca contra o **código** do Engine anterior/seguinte nessa ordem.
- `packages/README.md` tinha o mesmo problema para `planner-engine`→`intent-engine` (mesma classe de bug) — corrigido na mesma rodada, por consistência, mesmo não sendo escopo direto desta ADR (Planner/Intent são Releases 6/7, fora do que a Release 5 implementa) — documentado no Decision Log da Release 5A, não repetido aqui.
- Quando o Planner Engine (Release 6) for modelado, seu próprio `PLANNER_MODEL.md` decide se ele reaproveita o Value Object `Plan` de `packages/mission-engine` (via `packages/core`, movido lá) ou mantém sua própria representação interna que só se torna o `Plan` do Mission Engine no momento de publicar `mission.planned` — não decidido aqui, fica para quando essa Release for desenhada.
