# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 1 — SIGMA Protocol** (em andamento). Release 0 — Foundation aprovada pelo Product Owner e **publicada**: primeiro push realizado em 2026-08-04 para `github.com/FelipeGat/Project-SIGMA`, branch `main`. A partir desta aprovação, "Sprint" foi substituído por "Release" como unidade de entrega (ver [ADR-0024](../docs/adr/0024-terminologia-release.md)) e o projeto deixou de ser tratado como experimental — é produto oficial da Alfa Soluções.

Nenhum código de aplicação escrito ainda. A Release 2 — Kernel, originalmente a primeira Release de código, foi **adiada** em favor da Release 1 — SIGMA Protocol, por decisão do Product Owner: antes de construir qualquer Engine, definir como todos eles conversam. Ver [ADR-0025](../docs/adr/0025-protocol-antecede-kernel.md).

## O que existe

- Documentação de visão, produto, filosofia e horizonte de longo prazo: [VISION.md](../VISION.md), [MANIFESTO.md](../MANIFESTO.md), [PRODUCT.md](../PRODUCT.md), [VISION_2030.md](../VISION_2030.md).
- **[SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md)** — documento de maior autoridade técnica do projeto: Envelope de resposta padronizado, Capability, Intenção-não-Comando (Intent 1:N Mission), Autonomia Progressiva (4 níveis).
- Documentação estrutural: [KERNEL.md](../KERNEL.md), [PLUGIN_SYSTEM.md](../PLUGIN_SYSTEM.md), [EVENT_MODEL.md](../EVENT_MODEL.md), [TELEMETRY.md](../TELEMETRY.md), [WORKSPACES.md](../WORKSPACES.md), [MULTITENANCY.md](../MULTITENANCY.md), [MEMORY_ARCHITECTURE.md](../MEMORY_ARCHITECTURE.md).
- Arquitetura de alto nível por Engines (Kernel, Intent, Planner, Mission, Memory, Agent, Skill, Execution, Audit) — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- Glossário de domínio completo, incluindo Tenant/Workspace/Role/Capability e cardinalidade Intent 1:N Mission — [DOMAIN.md](../DOMAIN.md).
- 29 ADRs registrando as decisões arquiteturais tomadas até aqui — [docs/adr/](../docs/adr/).
- Convenções de nomenclatura e padrão de código, incluindo Capability e Envelope — [docs/conventions/](../docs/conventions/).
- Monorepo: `apps/`, `packages/` (12 pacotes), `services/`, `plugins/` (6 manifests com Capabilities e `autonomy_level_required`, schema formal), `tools/`, `docker/` — todos vazios (só README/manifest de escopo).
- Documentação inicial (sem código) de 4 Agents, 6 Skills, 7 Playbooks, `knowledge/` com 7 áreas.
- Governança formalizada em [council/](../council/): Product Owner, CTO, Lead Engineer, Creative, Documentation.
- Roadmap reestruturado por Release, com SIGMA Protocol antes do Kernel — [ROADMAP.md](../ROADMAP.md).

## O que não existe ainda

- Nenhuma linha de código de aplicação.
- Nenhuma Skill/Plugin, Agent ou Engine implementado de fato — tudo até aqui é especificação.
- Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner, ainda não criada.

## Bloqueios

Nenhum bloqueio de aprovação no momento — Release 1 está em execução (documentação, sem código) por instrução direta do Product Owner: "a próxima entrega não deve ser código do Kernel, e sim a especificação do SIGMA Protocol." Ver [NEXT.md](NEXT.md) para o que vem depois de SIGMA_PROTOCOL.md estar pronto para revisão.
