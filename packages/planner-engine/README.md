# packages/planner-engine

Implementação do Planner Engine: recebe uma Intent e decide o `Plan` de execução — Subtasks, ordem, candidatos de Agent/Skill. O único lugar do sistema onde esse tipo de decisão acontece; ver [ADR-0012](../../docs/adr/0012-planner-decide-nunca-a-ia.md). Primeira versão apoiada nos [Playbooks](../../playbooks/) já documentados.

Implementado na Release 6B. Camada L3 do [ROADMAP.md](../../ROADMAP.md).

Três camadas DDD, não quatro: **não tem `Infrastructure/`** — o Planner não persiste nada ([ADR-0095](../../docs/adr/0095-planner-sem-estado.md)). Sete `PlanTemplate`, um por Playbook; quando nenhum atende, publica `planning.failed` em vez de inventar um plano ([ADR-0097](../../docs/adr/0097-planner-falha-visivelmente.md)).

**Não depende de `packages/intent-engine`** — depende apenas de `core` e `kernel`. A afirmação contrária, que esteve aqui até 2026-08-11, contradizia [ADR-0031](../../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md) (o Planner é construído na Release 6, contra Intents estruturadas manualmente, **antes** de o Intent Engine existir na Release 7) e [ADR-0092](../../docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md), que corrigiu a mesma afirmação em `packages/README.md` — e passou batido neste arquivo. Ordem de Runtime não é dependência de código.
