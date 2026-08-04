# ADR-0038: Release 2 é o SIGMA Bootstrap — não o Kernel completo

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

A Release 2, até aqui chamada "Kernel", incluía em seu escopo bootstrap, contexto de execução, health-check, e o schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role — ver [ADR-0021](0021-multitenancy-desde-o-schema.md)), além de já prever o carregamento do Plugin System (ver [KERNEL.md](../../KERNEL.md)). Isso concentra responsabilidades demais na primeira Release de código: infraestrutura de boot, schema de negócio multiempresa, e descoberta de Plugins, todas juntas — dificultando validar cada uma isoladamente.

## Decisão

A Release 2 é renomeada para **SIGMA Bootstrap** e seu escopo é reduzido ao equivalente do bootstrap de uma aplicação (comparável ao `Application` do Laravel): Config, Logger, DI Container, Modules, Events (mecanismo, não os eventos de domínio), Lifecycle (boot/start/ready/shutdown), Health. Explicitamente **fora** do escopo: Missions, IA/Agents, Plugins. Especificação de como isso funciona em [BOOTSTRAP.md](../../BOOTSTRAP.md).

O schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role), antes bundlado na Release 2, precisa de uma nova casa — proposto mover para a Release 3 (Memory Engine) ou para uma Release própria entre Bootstrap e Memory, já que Memory/Mission precisam desse contexto para escopar dados corretamente. **Não decidido nesta ADR** — sinalizado para resolução na proposta formal da Release 2, antes de seu início.

O carregamento do Plugin System, também antes bundlado na Release 2/Kernel, é adiado para a Release 7 (Skill Engine), quando Plugins de fato existirem para serem carregados — o Bootstrap apenas registra a *capacidade* de o sistema, no futuro, carregar módulos dinamicamente (via seu mecanismo de `Modules`), sem conhecer Plugin nenhum ainda.

## Consequências

- A primeira Release de código fica pequena e verificável isoladamente: "o sistema inicia, carrega módulos, expõe health-check" — sem depender de nenhuma decisão de domínio (Mission, Tenant, Plugin) estar correta primeiro.
- [KERNEL.md](../../KERNEL.md) continua descrevendo o escopo conceitual completo do Kernel (incluindo carregamento de Plugins, contexto de Tenant/Workspace) — a Release 2 é o primeiro incremento desse escopo, não sua totalidade. Esta distinção precisa ficar explícita em toda referência cruzada, para não sugerir que tudo que KERNEL.md descreve chega pronto na Release 2.
- Documentos que já referenciavam "Release 2 — Kernel" como dona do schema de multiempresa ([MULTITENANCY.md](../../MULTITENANCY.md), [WORKSPACES.md](../../WORKSPACES.md), `services/auth/README.md`) precisam ser revisados quando a nova casa desse schema for decidida — não corrigidos nesta ADR, para não antecipar uma decisão que ainda depende da proposta formal da Release 2.
