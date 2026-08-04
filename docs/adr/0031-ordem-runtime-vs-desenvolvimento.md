# ADR-0031: Ordem de Runtime é distinta da Ordem de Desenvolvimento

- **Status**: Aceito — resolve a tensão sinalizada em [ADR-0025](0025-protocol-antecede-kernel.md)
- **Data**: 2026-08-04

## Contexto

[ADR-0025](0025-protocol-antecede-kernel.md) sinalizou, sem resolver, uma aparente inconsistência: o roadmap coloca o Planner Engine (Release 5) antes do Intent Engine (Release 6), mas o fluxo de execução real de uma Mission sempre passa por Intent antes de Planner (ver [ADR-0012](0012-planner-decide-nunca-a-ia.md), [ADR-0013](0013-intent-engine-como-porta-de-entrada.md), e a sequência canônica em [EVENT_MODEL.md](../../EVENT_MODEL.md)).

## Decisão

Duas ordens distintas e igualmente válidas coexistem, e devem ser nomeadas separadamente para não serem confundidas:

- **Ordem de Runtime** — como uma Mission percorre o sistema em produção: **Intent → Planner → Mission → Execution**.
- **Ordem de Desenvolvimento** — a sequência em que os Engines são construídos: **Protocol → Kernel/Bootstrap → Memory → Mission → Planner → Intent → Skill → Agent → ...** (ver [ROADMAP.md](../../ROADMAP.md)).

Um Engine é construído e testado contra um contrato já definido pelo SIGMA Protocol, usando entradas mockadas/manuais para dependências de runtime que ainda não existem como código — o Planner Engine (Release 5) é construído e testado contra Intents estruturadas manualmente, antes de o Intent Engine (Release 6) existir para produzi-las automaticamente a partir de linguagem natural.

## Consequências

- Fecha a tensão aberta em ADR-0025 sem mudar a ordem de Releases já definida — confirma que Planner antes de Intent, no desenvolvimento, é intencional.
- Toda proposta formal de Release a partir de agora declara explicitamente contra qual entrada mockada/manual ela será construída, quando a dependência de Ordem de Runtime ainda não existir em código.
- Reduz o risco de alguém (humano ou Agent) ler o roadmap e concluir, erroneamente, que o Planner deveria decidir sem uma Intent real — a Ordem de Runtime nunca muda; só a ordem em que o código é escrito.
