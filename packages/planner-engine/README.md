# packages/planner-engine

Implementação do Planner Engine: recebe uma Intent e decide o `Plan` de execução — Subtasks, ordem, candidatos de Agent/Skill. O único lugar do sistema onde esse tipo de decisão acontece; ver [ADR-0012](../../docs/adr/0012-planner-decide-nunca-a-ia.md). Primeira versão apoiada nos [Playbooks](../../playbooks/) já documentados.

Vazio na Fase Foundation. Camada L3 do [ROADMAP.md](../../ROADMAP.md), depende de `packages/intent-engine`.
