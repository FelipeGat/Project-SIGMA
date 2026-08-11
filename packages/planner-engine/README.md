# packages/planner-engine

Implementação do Planner Engine: recebe uma Intent e decide o `Plan` de execução — Subtasks, ordem, candidatos de Agent/Skill. O único lugar do sistema onde esse tipo de decisão acontece; ver [ADR-0012](../../docs/adr/0012-planner-decide-nunca-a-ia.md). Primeira versão apoiada nos [Playbooks](../../playbooks/) já documentados.

Vazio até a Release 6. Camada L3 do [ROADMAP.md](../../ROADMAP.md).

**Não depende de `packages/intent-engine`** — depende apenas de `core` e `kernel`. A afirmação contrária, que esteve aqui até 2026-08-11, contradizia [ADR-0031](../../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md) (o Planner é construído na Release 6, contra Intents estruturadas manualmente, **antes** de o Intent Engine existir na Release 7) e [ADR-0092](../../docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md), que corrigiu a mesma afirmação em `packages/README.md` — e passou batido neste arquivo. Ordem de Runtime não é dependência de código.
