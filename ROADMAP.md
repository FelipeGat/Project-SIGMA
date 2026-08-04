# Roadmap — SIGMA

O SIGMA avança **um épico por vez**, seguindo a ordem de dependência real entre os Engines do núcleo — não uma lista de features. Cada épico só entra em desenvolvimento após ser apresentado no formato Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entrega / Testes / Critérios de Aceite e **aprovado explicitamente**. Ver [ADR-0010](docs/adr/0010-processo-por-epicos-com-aprovacao.md) e [ADR-0015](docs/adr/0015-roadmap-por-camadas-nao-por-feature.md).

Este roadmap é a visão macro; o detalhamento formal de cada épico é produzido logo antes de sua execução, não com meses de antecedência — desenhar em detalhe um épico distante hoje só gera retrabalho quando o contexto mudar.

## Status

| Camada | Nome | Status |
|---|---|---|
| Foundation | Documentação, arquitetura, estrutura (Sprint 0 + 0.1) | 🔵 Em andamento |
| L1 | Kernel | ⚪ Não iniciado |
| L2 | Intent Engine | ⚪ Não iniciado |
| L3 | Planner Engine | ⚪ Não iniciado |
| L4 | Mission Engine | ⚪ Não iniciado |
| L5 | Memory Engine | ⚪ Não iniciado |
| L6 | Agent Engine | ⚪ Não iniciado |
| L7 | Skill Engine | ⚪ Não iniciado |
| L8 | Execution Engine | ⚪ Não iniciado |
| L9 | Audit Engine | ⚪ Não iniciado |
| L10 | Interfaces (Web PWA + Mobile) | ⚪ Não iniciado |
| L11 | Automation Engine | ⚪ Não iniciado |
| L12 | Analytics | ⚪ Não iniciado |

## Foundation (atual)

Entrega apenas documentação e estrutura de projeto. Nenhum código de aplicação. Escopo do Sprint 0: README, VISION, ARCHITECTURE, ROADMAP, estrutura inicial de diretórios, convenções de nomenclatura, ADR-0001–0010, CONTRIBUTING, CODE_OF_CONDUCT, LICENSE. Escopo do Sprint 0.1 (revisão): MANIFESTO, PRODUCT, VISION_2030, DOMAIN, pastas `agents/`, `skills/`, `knowledge/`, `playbooks/`, `memory/`, ADR-0011–0015, roadmap reestruturado por camada. Escopo do Sprint 0.2 (organização estrutural): monorepo reorganizado em `apps/`, `packages/`, `services/`, `plugins/`, `tools/`, `docker/`; KERNEL, PLUGIN_SYSTEM, EVENT_MODEL, TELEMETRY, WORKSPACES, MULTITENANCY, MEMORY_ARCHITECTURE; `/council`; ADR-0016–0023.

Critério de saída: aprovação explícita do responsável pelo projeto antes de qualquer linha de código de aplicação.

## L1 — Kernel

Bootstrap da plataforma: configuração por ambiente, contexto de execução, health-check, versionamento interno, bootstrap de Telemetry (ver [TELEMETRY.md](TELEMETRY.md)) e do `services/event-bus`. Inclui o schema fundacional de multiempresa — Tenant, Company, Workspace, User, Role (ver [MULTITENANCY.md](MULTITENANCY.md) e [ADR-0021](docs/adr/0021-multitenancy-desde-o-schema.md)) — desde a primeira migration, não retrofitado depois. Nenhuma regra de negócio de domínio — é a fundação sobre a qual todo Engine seguinte roda. Sem este épico, nenhum outro tem onde existir.

## L2 — Intent Engine

Recebe linguagem natural ou evento estruturado e produz uma `Intent`. Sem Planner ainda — o critério de aceite é a Intent corretamente estruturada e persistida, não uma ação executada. Ver [ADR-0013](docs/adr/0013-intent-engine-como-porta-de-entrada.md).

## L3 — Planner Engine

Recebe uma Intent e produz um `Plan` (Subtasks candidatas, Agent/Skill sugeridos por Subtask). Primeira versão pode se apoiar fortemente nos [Playbooks](playbooks/) já documentados como heurística inicial, antes de qualquer aprendizado via Memory Engine. Ver [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md).

## L4 — Mission Engine

Entidade Mission, máquina de estados do ciclo de vida (ver [ARCHITECTURE.md §6](docs/architecture/ARCHITECTURE.md)), persistência, API REST mínima (criar Mission a partir de um Plan, consultar status, acompanhar Subtasks), eventos de domínio publicados no Event Bus. Primeiro ponto em que um fluxo Intent → Plan → Mission é validável ponta a ponta — ainda sem Agent real executando (Subtask simulada/mockada).

## L5 — Memory Engine

Modelagem e primeira persistência/consulta de Knowledge e Memory, alimentadas pelos eventos de domínio já publicados desde o L4. Primeira fonte real de Knowledge: o conteúdo já existente em [/knowledge](knowledge/).

## L6 — Agent Engine

Entidades IA (provedor) e Agent (persona), contrato `AgentPort`. Primeira integração real com um provedor de IA (Claude, para o Agent de Engenharia — ver [agents/claude.md](agents/claude.md)). Uma Subtask passa a ser de fato delegada a um Agent real, não mais simulada.

## L7 — Skill Engine

Entidade Skill, mecanismo de descoberta e carregamento de Plugins (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) e [ADR-0017](docs/adr/0017-plugin-system.md)), contrato completo (Config/Permissions/Input/Output/Events/Logs/Tests/Docs) implementado de fato através de um primeiro Plugin ponta a ponta (candidato: `gestor`, já especificado em [plugins/gestor/manifest.json](plugins/gestor/manifest.json), por já existir API e caso de uso real no ecossistema Alfa). Primeiro ponto em que o SIGMA age de verdade sobre um sistema externo.

## L8 — Execution Engine

Acompanhamento e validação da execução de Subtasks em andamento: retry, timeout, critério de sucesso/falha por tipo de Subtask. Fecha o ciclo Intent → Plan → Mission → Agent → Skill → **validação**.

## L9 — Audit Engine

Persistência estruturada de Events e Logs com correlação completa (Mission → Subtask → Agent → Skill), e a API/consulta necessária para auditoria e para alimentar interfaces.

## L10 — Interfaces

React + TypeScript + Vite (PWA), Design System próprio, dark mode — dashboard de Missions em tempo real via WebSocket. React Native + Expo, mesmo Design System e mesmo backend, em seguida ou em paralelo conforme capacidade de execução no momento.

## L11 — Automation Engine

Motor de automação declarativa reagindo a eventos de domínio (ex: "quando uma Mission do tipo Novo Orçamento concluir, disparar a Mission de Nova Obra").

## L12 — Analytics

Métricas e indicadores sobre o que o SIGMA orquestra (tempo médio de Mission por tipo, taxa de falha por Skill, uso por Agent) — consome o histórico já registrado pelo Audit Engine.

---

Este roadmap é revisado ao final de cada camada concluída — a ordem e o escopo das camadas seguintes podem mudar com base no que for aprendido.
