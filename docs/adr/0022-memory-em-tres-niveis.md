# ADR-0022: Memory organizada em três níveis — Operational, Project, Long Term

- **Status**: Aceito — refina o Memory Engine descrito em [ADR-0011](0011-arquitetura-em-camadas-de-engines.md)
- **Data**: 2026-08-04

## Contexto

Tratar "o que o SIGMA aprendeu" como um único armazenamento indiferenciado cria um risco concreto: contexto efêmero e possivelmente incorreto de uma única Mission (ex: uma informação mal interpretada numa reunião) poderia se misturar, sem distinção, com conhecimento institucional validado ao longo de meses. Isso contaminaria decisões futuras do Planner Engine com ruído tratado como fato estabelecido.

## Decisão

Memory é organizada em três níveis — Operational Memory (escopo de uma Mission em execução), Project Memory (escopo de um Workspace específico) e Long Term Memory (escopo de toda a organização) — com promoção entre níveis dependente de repetição ou generalização, nunca automática de Operational direto para Long Term. Detalhamento em [MEMORY_ARCHITECTURE.md](../../MEMORY_ARCHITECTURE.md).

## Consequências

- Um Agent que consulta Memory sabe (ou pode saber) o nível de confiança da informação — algo observado uma vez numa Mission específica carrega peso diferente de um padrão consolidado em Long Term Memory.
- Reduz o risco de "aprendizado" espúrio a partir de uma única interação ruim se tornar, sem revisão, conhecimento institucional tratado como verdade.
- Exige uma mecânica de promoção entre níveis (a ser definida em detalhe no épico do Memory Engine, Release 3) — complexidade adicional em troca de Memory ser confiável, não apenas grande.
- Conecta-se a [WORKSPACES.md](../../WORKSPACES.md): Project Memory é escopada por Workspace, não por Project isolado, pela mesma razão que motivou [ADR-0020](0020-workspace-como-unidade-de-contexto.md).
