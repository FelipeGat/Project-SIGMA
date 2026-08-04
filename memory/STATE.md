# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 1 — SIGMA Protocol: aprovada, push realizado.** **Release 2 — SIGMA Bootstrap: aprovada com alteração obrigatória** pelo CTO — proposta revisada (revisão 2) incorporando Identity Engine como Release própria, Bootstrap desacoplado de Engines, System Manifest, Self-Describing Components, lifecycle estendido, health estilo Kubernetes. Aguardando confirmação final do Product Owner sobre a proposta atualizada antes da primeira linha de código.

## O que existe

- Documentação de visão, produto, filosofia (Declarativo-não-Imperativo) e horizonte de longo prazo: [VISION.md](../VISION.md), [MANIFESTO.md](../MANIFESTO.md), [PRODUCT.md](../PRODUCT.md), [VISION_2030.md](../VISION_2030.md).
- **[SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md)** — Envelope v2, Capability Registry, Intenção-não-Comando, Autonomia Progressiva, Ordem de Runtime vs. Desenvolvimento.
- **[SGL.md](../SGL.md)**, **[DIGITAL_TWIN.md](../DIGITAL_TWIN.md)**, **[BOOTSTRAP.md](../BOOTSTRAP.md)** (reescrito: Module-only, lifecycle `discover→register→boot→start→ready→degraded→shutdown`, health `/health/live|ready|startup`, Telemetry, Configuration Provider), **[SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md)** (novo: System Manifest + Self-Describing Components).
- Documentação estrutural: [KERNEL.md](../KERNEL.md) (reescrito: Kernel nunca conhece Engine, só Module), [PLUGIN_SYSTEM.md](../PLUGIN_SYSTEM.md), [EVENT_MODEL.md](../EVENT_MODEL.md), [TELEMETRY.md](../TELEMETRY.md), [WORKSPACES.md](../WORKSPACES.md), [MULTITENANCY.md](../MULTITENANCY.md) (schema de multiempresa agora atribuído ao Identity Engine), [MEMORY_ARCHITECTURE.md](../MEMORY_ARCHITECTURE.md).
- Arquitetura de alto nível — agora **dez Engines** (Identity Engine acrescentado) — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- Glossário de domínio — [DOMAIN.md](../DOMAIN.md).
- 47 ADRs — [docs/adr/](../docs/adr/).
- Monorepo: `apps/`, `packages/` (13 pacotes, incluindo `identity-engine/` novo), `services/`, `plugins/` (6 manifests), `tools/`, `docker/`.
- Documentação inicial (sem código) de 4 Agents, 6 Skills, 7 Playbooks, `knowledge/` com 7 áreas.
- Governança formalizada em [council/](../council/).
- **Processo de Decision Log** formalizado — toda Release que produz código produz também um Decision Log (`docs/releases/000N-<slug>-decision-log.md`) — [ADR-0047](../docs/adr/0047-decision-log-por-release.md), [CONTRIBUTING.md](../CONTRIBUTING.md).
- Roadmap com 15 Releases (0–14), Identity Engine inserido como Release 3 — [ROADMAP.md](../ROADMAP.md).
- Proposta revisada da Release 2 — [docs/releases/0002-sigma-bootstrap.md](../docs/releases/0002-sigma-bootstrap.md).

## Pendências sinalizadas, aguardando confirmação do Product Owner

- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" tratado como campo `objective` de uma Intent, não camada nova. Proposto, não confirmado — relevante antes da Release 6/7.
- **Confirmação final da proposta revisada da Release 2** — se aprovada como está, o próximo passo é código; se algo ainda precisa ajuste, aguardo indicação.

## O que não existe ainda

- Nenhuma linha de código de aplicação.
- Nenhum Engine, Plugin, Skill ou Agent implementado de fato.

## Bloqueios

Nenhum processual. Aguardando confirmação explícita do Product Owner de que a proposta revisada da Release 2 está pronta para virar código — o Product Owner indicou que autorizaria após a atualização, mas essa atualização não havia sido apresentada ainda antes desta revisão. Ver [NEXT.md](NEXT.md).
