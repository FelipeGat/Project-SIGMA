# ADR-0016: Monorepo reorganizado em apps/packages/services/plugins/tools/docs/docker

- **Status**: Aceito — substitui a estrutura de [ADR-0002](0002-estrutura-de-monorepo.md)
- **Data**: 2026-08-04

## Contexto

A estrutura original (`backend/`, `frontend-web/`, `frontend-mobile/`) tratava o SIGMA como um backend único mais dois clientes — uma forma monolítica de organizar o repositório, mesmo com módulos internos desacoplados. Em revisão, foi apontado que essa estrutura não reflete a arquitetura de nove Engines já decidida ([ADR-0011](0011-arquitetura-em-camadas-de-engines.md)) nem o horizonte de decomposição em repositórios especializados descrito em [VISION_2030.md](../../VISION_2030.md).

## Decisão

O monorepo passa a seguir a estrutura:

```
project-sigma/
├── apps/        # superfícies de usuário: web, mobile, admin, telegram, cli
├── packages/     # bibliotecas: os nove Engines, core, design-system, sdk
├── services/      # processos deployáveis: gateway, auth, scheduler, notifications, ai-router, event-bus
├── plugins/        # implementação técnica de cada Skill (ver ADR-0017)
├── docs/            # arquitetura, ADRs, convenções
├── tools/            # ferramentas de desenvolvimento do monorepo
└── docker/            # containers de ambiente local e deploy
```

Mecânica técnica no stack já decidido ([ADR-0009](0009-stack-tecnologica-de-referencia.md)): cada pacote em `packages/` é um pacote Composer/NPM local, referenciado pelos consumidores em `apps/`/`services/` via path repository — sem publicação em registry externo enquanto o monorepo for a única forma de consumo.

## Consequências

- A estrutura do repositório passa a espelhar a arquitetura de Engines, não uma divisão arbitrária backend/frontend — reduz a distância entre "como o sistema é desenhado" e "onde o código correspondente vive".
- Cada pacote pode evoluir, ser testado e (no horizonte de VISION_2030.md) ser extraído para repositório próprio de forma independente, sem reescrita.
- Introduz fronteiras de pacote antes de qualquer Engine ter sido implementado uma única vez — um custo real: cross-cutting refactors entre Engines exigem alterar múltiplos `composer.json`/`package.json` em vez de um único módulo, e as fronteiras certas só ficam claras depois de algum uso real. Aceito conscientemente como o preço de começar organizado, dado o horizonte de dezenas de repositórios em [VISION_2030.md](../../VISION_2030.md).
- Backend deixa de ser um conceito único — a antiga pasta `backend/` se distribui entre `packages/*-engine` (lógica de domínio) e `services/gateway` (a aplicação Laravel que os monta e expõe via HTTP/WebSocket).
