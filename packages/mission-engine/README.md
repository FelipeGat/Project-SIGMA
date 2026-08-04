# packages/mission-engine

Implementação do Mission Engine: entidade `Mission` (agregado raiz do domínio — ver [ADR-0003](../../docs/adr/0003-mission-como-entidade-central.md)), máquina de estados do ciclo de vida (ver [MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md), que reconcilia e substitui o esboço em [ARCHITECTURE.md §6](../../docs/architecture/ARCHITECTURE.md)), acompanhamento de Subtasks a partir de um `Plan` — consumido como dado de entrada, nunca resolvendo `planner-engine` (código ainda não existe; Release 5 é construída antes da Release 6, ver [ADR-0031](../../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md)).

Vazio na Fase Foundation. Release 5 do [ROADMAP.md](../../ROADMAP.md), depende só de `core`/`kernel`.
