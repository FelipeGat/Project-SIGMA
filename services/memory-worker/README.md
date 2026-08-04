# services/memory-worker

Casca de execução do Memory Engine (`packages/memory-engine`) — sem HTTP. Sobe o Kernel normalmente (`Bootstrap`, mesmo padrão de `services/auth`), depois entra num loop bloqueante (`bin/worker.php`) consumindo `identity.created`/`workspace.selected` do Redis via `RedisSubscriber` — o primeiro listener Redis cross-processo real do projeto (ver [docs/releases/0004b-memory-infrastructure.md](../../docs/releases/0004b-memory-infrastructure.md)).

Nenhuma API pública nesta Release — não existe consumidor real do Memory Engine ainda (Mission/Planner/Agent Engine). Rodar: `php bin/worker.php`, ou via `docker compose up memory-worker`.
