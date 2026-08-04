# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 1 — SIGMA Protocol: aprovada pelo Product Owner, push realizado.** Project SIGMA oficialmente iniciado como produto. Proposta formal da **Release 2 — SIGMA Bootstrap** em preparação, conforme instruído — nenhum código de aplicação escrito ainda, e nenhum será até essa proposta ser revisada e aprovada.

## O que existe

- Documentação de visão, produto, filosofia (incluindo o princípio Declarativo-não-Imperativo) e horizonte de longo prazo: [VISION.md](../VISION.md), [MANIFESTO.md](../MANIFESTO.md), [PRODUCT.md](../PRODUCT.md), [VISION_2030.md](../VISION_2030.md).
- **[SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md)** — Envelope v2 (correlationId, actor, intent, capability, audit...), Capability e Capability Registry, Intenção-não-Comando (Intent 1:N Mission, campo `objective`), Autonomia Progressiva, Ordem de Runtime vs. Desenvolvimento.
- **[SGL.md](../SGL.md)** — gramática da SIGMA Language e mapeamento para o Envelope.
- **[DIGITAL_TWIN.md](../DIGITAL_TWIN.md)** — representação viva de Client/Project/Company/User, nunca leitura direta a sistema externo.
- **[BOOTSTRAP.md](../BOOTSTRAP.md)** — design de referência da Release 2: boot, Modules, DI, ciclo de vida.
- Documentação estrutural: [KERNEL.md](../KERNEL.md), [PLUGIN_SYSTEM.md](../PLUGIN_SYSTEM.md), [EVENT_MODEL.md](../EVENT_MODEL.md) (agora com 3 camadas de evento), [TELEMETRY.md](../TELEMETRY.md), [WORKSPACES.md](../WORKSPACES.md), [MULTITENANCY.md](../MULTITENANCY.md), [MEMORY_ARCHITECTURE.md](../MEMORY_ARCHITECTURE.md).
- Arquitetura de alto nível por Engines — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- Glossário de domínio completo, incluindo Digital Twin, Capability Registry, Objetivo — [DOMAIN.md](../DOMAIN.md).
- 38 ADRs — [docs/adr/](../docs/adr/).
- Monorepo: `apps/`, `packages/` (12 pacotes), `services/`, `plugins/` (6 manifests com Capability Registry — version/owner/dependencies), `tools/`, `docker/`.
- Documentação inicial (sem código) de 4 Agents, 6 Skills, 7 Playbooks, `knowledge/` com 7 áreas.
- Governança formalizada em [council/](../council/).
- Roadmap por Release, renumerado e com a Release 2 renomeada para SIGMA Bootstrap (escopo reduzido a infraestrutura pura) — [ROADMAP.md](../ROADMAP.md).

## Pendências sinalizadas, aguardando confirmação do Product Owner

- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" foi tratado como o campo `objective` de uma Intent, não como camada nova acima dela. Proposto, não confirmado.
- **Onde vive o schema fundacional de multiempresa** (Tenant/Company/Workspace/User/Role) agora que a Release 2 foi reduzida a infraestrutura pura — proposto para a Release 3, a confirmar na proposta formal da Release 2.

## O que não existe ainda

- Nenhuma linha de código de aplicação.
- Nenhuma Skill/Plugin, Agent ou Engine implementado de fato.

## Bloqueios

Nenhum. Trabalhando na proposta formal da Release 2 — SIGMA Bootstrap (Objetivo/Escopo/Arquitetura/Dependências/Riscos/Entregáveis/Testes/Critérios de Aceite), por instrução direta do Product Owner. Código só começa após essa proposta ser revisada e aprovada. Ver [NEXT.md](NEXT.md).
