# ADR-0020: Workspace como unidade de contexto operacional

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O modelo de domínio original tratava `Project` como a unidade que dá contexto a uma Mission. Na prática, uma situação de negócio real (ex: "Cliente Brenno") envolve mais do que um Project isolado — envolve Company, múltiplos Projects, Budgets, Meetings, Documents e histórico de comunicação, tudo relacionado. Exigir que cada Mission busque e relacione essas entidades manualmente a cada vez contraria o objetivo central do SIGMA: reduzir o esforço de alguém (ou de um Agent) carregar contexto na cabeça.

## Decisão

Introduz-se `Workspace` como a unidade de contexto operacional do SIGMA — um agrupamento de tudo que é relevante para uma situação de negócio específica, tipicamente um cliente. Uma Mission executa dentro de um Workspace, que resolve automaticamente as entidades de negócio relacionadas via Skill. Detalhamento em [WORKSPACES.md](../../WORKSPACES.md).

## Consequências

- Reduz ambiguidade na interpretação de uma Intent — "participe da reunião do cliente Brenno" dentro do Workspace "Cliente Brenno" já ativo não exige desambiguação adicional.
- `Workspace` não é um Engine novo — é resolvido como parte do contexto de execução do Kernel, evitando a criação de um décimo Engine para um conceito que é, fundamentalmente, agregação de dados já modelados em outros contextos.
- Exige que o Kernel resolva e mantenha o Workspace ativo como parte do contexto de execução transversal (Tenant/Workspace/User) desde a Release 2 — não pode ser adicionado depois sem revisar o schema base, o que conecta esta decisão diretamente a [ADR-0021](0021-multitenancy-desde-o-schema.md).
- Nenhum dado é duplicado dentro de um Workspace — tudo é agregado via Skill a partir da fonte de origem; um Workspace some de vista sem apagar nenhum dado de negócio real.
