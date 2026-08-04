# Roadmap — SIGMA

O SIGMA avança **uma Release por vez** ("Release" substitui "Sprint" a partir da aprovação da Release 0 — ver [ADR-0024](docs/adr/0024-terminologia-release.md)). Cada Release de código só entra em desenvolvimento após ser apresentada no formato Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entrega / Testes / Critérios de Aceite e **aprovada explicitamente**. Ver [ADR-0010](docs/adr/0010-processo-por-epicos-com-aprovacao.md) e [ADR-0015](docs/adr/0015-roadmap-por-camadas-nao-por-feature.md).

Este roadmap é a visão macro; o detalhamento formal de cada Release é produzido logo antes de sua execução, não com meses de antecedência — desenhar em detalhe uma Release distante hoje só gera retrabalho quando o contexto mudar.

## Status

| Release | Nome | Status |
|---|---|---|
| 0 | Foundation (documentação, arquitetura, estrutura) | ✅ Aprovada, publicada |
| 1 | SIGMA Protocol | ✅ Aprovada, push realizado |
| 2 | SIGMA Bootstrap | ✅ Implementada — 48 testes passando |
| 3 | Identity Engine | ✅ Aprovada para implementação — [IDENTITY_MODEL.md](IDENTITY_MODEL.md)/[IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md)/`Identity.contract.yaml` entregues, código ainda não iniciado |
| 4 | Memory Engine | ⚪ Não iniciado |
| 5 | Mission Engine | ⚪ Não iniciado |
| 6 | Planner Engine | ⚪ Não iniciado |
| 7 | Intent Engine | ⚪ Não iniciado |
| 8 | Skill Engine | ⚪ Não iniciado |
| 9 | Agent Engine | ⚪ Não iniciado |
| 10 | Execution Engine | ⚪ Não iniciado |
| 11 | Audit Engine | ⚪ Não iniciado |
| 12 | Interfaces (Web PWA + Mobile) | ⚪ Não iniciado |
| 13 | Automation Engine | ⚪ Não iniciado |
| 14 | Analytics | ⚪ Não iniciado |

## Release 0 — Foundation ✅

Entregou apenas documentação e estrutura de projeto, nenhum código de aplicação, em três rodadas de revisão (internamente chamadas Sprint 0, 0.1, 0.2 — terminologia anterior à [ADR-0024](docs/adr/0024-terminologia-release.md), não renomeada retroativamente): README, VISION, MANIFESTO, PRODUCT, VISION_2030, DOMAIN, ARCHITECTURE, arquitetura em 9 Engines, monorepo `apps/packages/services/plugins/tools/docker`, KERNEL/PLUGIN_SYSTEM/EVENT_MODEL/TELEMETRY/WORKSPACES/MULTITENANCY/MEMORY_ARCHITECTURE, `agents/`, `skills/`, `knowledge/`, `playbooks/`, `council/`, `memory/`, ADR-0001–0023, CONTRIBUTING, CODE_OF_CONDUCT, LICENSE. Aprovada pelo Product Owner; primeiro push realizado em 2026-08-04 (`github.com/FelipeGat/Project-SIGMA`, branch `main`).

## Release 1 — SIGMA Protocol ✅

Especificação do protocolo de comunicação entre todas as peças do SIGMA, **antes** de qualquer Engine ser implementado — ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md) e [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md). Escopo: Envelope de resposta padronizado ([ADR-0026](docs/adr/0026-envelope-de-resposta-padronizado.md), [ADR-0030](docs/adr/0030-envelope-v2.md)), Capability e Capability Registry ([ADR-0027](docs/adr/0027-capability-unidade-de-skill.md), [ADR-0033](docs/adr/0033-capability-registry.md)), execução por Intenção com decomposição em múltiplas Missions ([ADR-0028](docs/adr/0028-intencao-nao-comando.md)), SIGMA Language ([ADR-0032](docs/adr/0032-sigma-language.md), [SGL.md](SGL.md)), Digital Twin ([ADR-0035](docs/adr/0035-digital-twin.md), [DIGITAL_TWIN.md](DIGITAL_TWIN.md)), eventos em três camadas ([ADR-0034](docs/adr/0034-eventos-tres-camadas.md)), Autonomia Progressiva ([ADR-0029](docs/adr/0029-autonomia-progressiva.md)), princípio Declarativo-não-Imperativo ([ADR-0037](docs/adr/0037-declarativo-nao-imperativo.md)). Aprovada pelo Product Owner; push realizado.

## Release 2 — SIGMA Bootstrap (proposta formal em [docs/releases/0002-sigma-bootstrap.md](docs/releases/0002-sigma-bootstrap.md), aguardando aprovação)

Renomeada de "Kernel" — escopo reduzido ao bootstrap puro da plataforma, equivalente ao `Application` do Laravel/Spring Boot: Configuration Provider, Telemetry (Logs/Metrics/Tracing/Audit — não só Logger), DI Container, Modules (nunca Engines — ver nota abaixo), System Manifest, Lifecycle estendido (`discover → register → boot → start → ready → degraded → shutdown`), Health compatível com Kubernetes (`/health/live`, `/health/ready`, `/health/startup`), princípio de Self-Describing Components. **Fora do escopo**: Missions, IA/Agents, carregamento de Plugin real. Ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md), [ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md)–[ADR-0046](docs/adr/0046-self-describing-components.md) e [BOOTSTRAP.md](BOOTSTRAP.md)/[SYSTEM_MANIFEST.md](SYSTEM_MANIFEST.md). **✅ Implementada.** Proposta final (revisão 3) em [docs/releases/0002-sigma-bootstrap.md](docs/releases/0002-sigma-bootstrap.md), Decision Log em [docs/releases/0002-sigma-bootstrap-decision-log.md](docs/releases/0002-sigma-bootstrap-decision-log.md). Primeiro código de aplicação do projeto — `packages/core`, `packages/kernel`, `services/event-bus`, `services/gateway`, 48 testes automatizados passando, validado com HTTP real via `php -S`. Implementa o Envelope de resposta da Release 1 desde o primeiro endpoint de health-check.

O schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role — [MULTITENANCY.md](MULTITENANCY.md)), antes bundlado nesta Release, não vive aqui nem no Memory Engine — tem Release própria, ver Release 3 — Identity Engine abaixo.

## Release 3 — Identity Engine

Responde "quem sou, quem é o usuário, qual empresa, qual workspace, qual tenant, quais permissões, qual autonomia, qual contexto" — deliberadamente extraído do Memory Engine, por ser identidade e não memória (ver [ADR-0039](docs/adr/0039-identity-engine.md)). Contém o schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role, `tenant_id` obrigatório desde a primeira migration — [MULTITENANCY.md](MULTITENANCY.md), [ADR-0021](docs/adr/0021-multitenancy-desde-o-schema.md)) e a resolução do nível de Autonomia Progressiva por User/Role ([ADR-0029](docs/adr/0029-autonomia-progressiva.md)). Todo Engine seguinte consome o contexto de identidade resolvido por este Engine através do Kernel — nunca resolve Tenant/Workspace por conta própria.

## Release 4 — Memory Engine

Modelagem e primeira persistência/consulta de Knowledge e Memory nos três níveis (ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md)), custódia dos primeiros Digital Twins (ver [DIGITAL_TWIN.md](DIGITAL_TWIN.md)). Primeira fonte real de Knowledge: o conteúdo já existente em [/knowledge](knowledge/). Construído antes do Mission Engine para que o Planner (Release 6) já tenha uma fonte de contexto/heurística ao nascer. Consome identidade/contexto da Release 3, não os resolve por conta própria.

## Release 5 — Mission Engine

Entidade Mission, máquina de estados do ciclo de vida (ver [ARCHITECTURE.md §6](docs/architecture/ARCHITECTURE.md)), persistência, API REST mínima, eventos de domínio publicados no Event Bus. Suporta a cardinalidade Intent 1:N Mission desde o início (ver [ADR-0028](docs/adr/0028-intencao-nao-comando.md)), ainda que a Release 1:N completa só se materialize quando Planner (Release 6) e Intent (Release 7) existirem.

## Release 6 — Planner Engine

Recebe uma Intent (ainda estruturada manualmente/mockada — Intent Engine só nasce na Release 7, ver a nota de transparência em [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md)) e decompõe em uma ou mais Missions (Plan + Subtasks candidatas, Agent/Skill sugeridos). Primeira versão apoiada nos [Playbooks](playbooks/) já documentados como heurística inicial. Ver [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md).

## Release 7 — Intent Engine

Recebe linguagem natural ou evento estruturado e produz uma `Intent` real, substituindo as Intents mockadas usadas para construir a Release 6. Ver [ADR-0013](docs/adr/0013-intent-engine-como-porta-de-entrada.md).

## Release 8 — Skill Engine

Entidade Skill, mecanismo de descoberta e carregamento de Plugins (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) e [ADR-0017](docs/adr/0017-plugin-system.md)), Capabilities com autonomia por nível (ver [ADR-0027](docs/adr/0027-capability-unidade-de-skill.md) e [ADR-0029](docs/adr/0029-autonomia-progressiva.md)) implementadas de fato através de um primeiro Plugin ponta a ponta (candidato: `gestor`, já especificado em [plugins/gestor/manifest.json](plugins/gestor/manifest.json)). Primeiro ponto em que o SIGMA age de verdade sobre um sistema externo.

## Release 9 — Agent Engine

Entidades IA (provedor) e Agent (persona), contrato `AgentPort`. Primeira integração real com um provedor de IA (Claude, para o Agent de Engenharia — ver [agents/claude.md](agents/claude.md)). Uma Subtask passa a ser de fato delegada a um Agent real, respondendo no envelope padrão da Release 1.

## Release 10 — Execution Engine

Acompanhamento e validação da execução de Subtasks em andamento: retry, timeout, critério de sucesso/falha por tipo de Subtask.

## Release 11 — Audit Engine

Persistência estruturada de Events e Logs com correlação completa (Intent → Mission → Subtask → Agent → Skill), e a API/consulta necessária para auditoria e para alimentar interfaces.

## Release 12 — Interfaces

React + TypeScript + Vite (PWA), Design System próprio, dark mode — dashboard de Missions em tempo real via WebSocket. React Native + Expo, mesmo Design System e mesmo backend, em seguida ou em paralelo conforme capacidade de execução no momento.

## Release 13 — Automation Engine

Motor de automação declarativa reagindo a eventos de domínio (ex: "quando uma Mission do tipo Novo Orçamento concluir, disparar a Mission de Nova Obra").

## Release 14 — Analytics

Métricas e indicadores sobre o que o SIGMA orquestra (tempo médio de Mission por tipo, taxa de falha por Skill, uso por Agent) — consome o histórico já registrado pelo Audit Engine.

---

Este roadmap é revisado ao final de cada Release concluída — a ordem e o escopo das Releases seguintes podem mudar com base no que for aprendido.
