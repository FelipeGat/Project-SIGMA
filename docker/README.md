# docker

Definições de container para ambiente local e deploy dos `services/*` — um `Dockerfile` por serviço, mais `docker-compose` para orquestrar o ambiente de desenvolvimento local (MariaDB, Redis, os serviços do SIGMA).

Vazio na Fase Foundation, deliberadamente: um `docker-compose.yml` real já seria infraestrutura executável antes de existir qualquer serviço para orquestrar — nasce junto com a Release 2 — SIGMA Bootstrap do [ROADMAP.md](../ROADMAP.md), quando `services/gateway` e `services/event-bus` tiverem algo real para rodar.
