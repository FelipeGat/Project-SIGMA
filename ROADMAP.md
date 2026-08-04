# Roadmap — SIGMA

O SIGMA avança **uma Release por vez** ("Release" substitui "Sprint" a partir da aprovação da Release 0 — ver [ADR-0024](docs/adr/0024-terminologia-release.md)). Cada Release de código só entra em desenvolvimento após ser apresentada no formato Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entrega / Testes / Critérios de Aceite e **aprovada explicitamente**. Ver [ADR-0010](docs/adr/0010-processo-por-epicos-com-aprovacao.md) e [ADR-0015](docs/adr/0015-roadmap-por-camadas-nao-por-feature.md).

Este roadmap é a visão macro; o detalhamento formal de cada Release é produzido logo antes de sua execução, não com meses de antecedência — desenhar em detalhe uma Release distante hoje só gera retrabalho quando o contexto mudar. Estendido de 14 para 24 Releases em 2026-08-04, a partir de uma análise de longo prazo (5-10 anos) do Product Owner — ver [ADR-0070](docs/adr/0070-roadmap-estendido-24-releases.md).

## Status

| Release | Nome | Status | Maturidade |
|---|---|---|---|
| 0 | Foundation (documentação, arquitetura, estrutura) | ✅ Concluída | 100% |
| 1 | SIGMA Protocol | ✅ Concluída | 100% |
| 2 | SIGMA Bootstrap | ✅ Concluída | 100% |
| 3A | Identity Domain | ✅ Concluída | 100% |
| 3B | Identity Infrastructure | ✅ Concluída | 100% |
| 3.5 | Architecture Consolidation | ✅ Concluída | 100% |
| 4A | Memory Domain | ⏳ Proposta apresentada, aguardando aprovação | 0% |
| 4B | Memory Infrastructure | ⚪ Não iniciada | 0% |
| 5 | Mission Engine | ⏳ Não iniciada | 0% |
| 6 | Planner Engine | ⏳ Não iniciada | 0% |
| 7 | Intent Engine | ⏳ Não iniciada | 0% |
| 8 | Skill Engine | ⏳ Não iniciada | 0% |
| 9 | Agent Engine | ⏳ Não iniciada | 0% |
| 10 | Execution Engine | ⏳ Não iniciada | 0% |
| 11 | Audit Engine | ⏳ Não iniciada | 0% |
| 12 | Gateway/API | ⏳ Não iniciada | 0% |
| 13 | Interfaces (Web PWA + Mobile) | ⏳ Não iniciada | 0% |
| 14 | Automation Engine | ⏳ Não iniciada | 0% |
| 15 | Analytics | ⏳ Não iniciada | 0% |
| 16 | Knowledge Engine | ⏳ Não iniciada | 0% |
| 17 | Digital Twin (Engine própria) | ⏳ Não iniciada | 0% |
| 18 | Capability Registry | ⏳ Não iniciada | 0% |
| 19 | Council Engine | ⏳ Não iniciada | 0% |
| 20 | Multi-Agent Runtime | ⏳ Não iniciada | 0% |
| 21 | Marketplace | ⏳ Não iniciada | 0% |
| 22 | Cloud Sync | ⏳ Não iniciada | 0% |
| 23 | Production Hardening | ⏳ Não iniciada | 0% |
| 24 | SIGMA v1.0 | ⏳ Não iniciada | 0% |

> **Release 6/7 — pendência sinalizada, não decidida silenciosamente**: a numeração acima mantém `6 — Planner`, `7 — Intent`, conforme [ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md) (deliberado: Planner é construído primeiro com Intents mockadas). A visão de longo prazo mais recente do Product Owner listou `6 — Intent`, `7 — Planner` — pode ter sido uma simplificação da tabela de alto nível, ou uma intenção real de reabrir o ADR-0031. Fica como pergunta em aberto até confirmação explícita — ver [ADR-0070](docs/adr/0070-roadmap-estendido-24-releases.md).

## Componentes estruturais sinalizados, ainda sem Release própria

Mapeados pelo Product Owner como necessidades arquiteturais que vão aparecer antes da Release 24, mas que ainda não têm um número de Release atribuído — cada um provavelmente se encaixa dentro de uma Release já listada acima, a confirmar quando a Release correspondente for desenhada em detalhe:

| Componente | O que resolve | Onde provavelmente se encaixa |
|---|---|---|
| **Scheduler** | Execução agendada (ex: "08:00 → Executar Missão → Enviar relatório → Fechar Sprint") | Release própria a definir, ou dentro de Automation Engine (14) |
| **Secrets Manager** | Credenciais de provedores (OpenAI, Claude, GitHub, Telegram, SMTP, Banco) criptografadas — hoje só `Configuration Provider` por Module, sem criptografia | Production Hardening (23) ou release própria |
| **Cache Layer** | Mission, Memory e Planner vão precisar de cache — não existe ainda | A confirmar quando Memory Engine (4) ou Mission Engine (5) forem desenhados em detalhe |
| **Observability** | Tracing, Metrics, Performance, Latency, Errors — hoje só Logs (ver [TELEMETRY.md](TELEMETRY.md)) | Expansão do Audit Engine (11) |
| **Policy Engine** | Unifica "pode? até quanto? precisa aprovação? executa sozinho?" — hoje espalhado entre Autonomia Progressiva ([SIGMA_PROTOCOL.md §5](SIGMA_PROTOCOL.md#5-autonomia-progressiva)) e Permission ([IDENTITY_MODEL.md](IDENTITY_MODEL.md#permission)) | Capability Registry (18) ou release própria |

## Release 0 — Foundation ✅

Entregou apenas documentação e estrutura de projeto, nenhum código de aplicação, em três rodadas de revisão (internamente chamadas Sprint 0, 0.1, 0.2 — terminologia anterior à [ADR-0024](docs/adr/0024-terminologia-release.md), não renomeada retroativamente): README, VISION, MANIFESTO, PRODUCT, VISION_2030, DOMAIN, ARCHITECTURE, arquitetura em 9 Engines, monorepo `apps/packages/services/plugins/tools/docker`, KERNEL/PLUGIN_SYSTEM/EVENT_MODEL/TELEMETRY/WORKSPACES/MULTITENANCY/MEMORY_ARCHITECTURE, `agents/`, `skills/`, `knowledge/`, `playbooks/`, `council/`, `memory/`, ADR-0001–0023, CONTRIBUTING, CODE_OF_CONDUCT, LICENSE. Aprovada pelo Product Owner; primeiro push realizado em 2026-08-04 (`github.com/FelipeGat/Project-SIGMA`, branch `main`).

## Release 1 — SIGMA Protocol ✅

Especificação do protocolo de comunicação entre todas as peças do SIGMA, **antes** de qualquer Engine ser implementado — ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md) e [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md). Escopo: Envelope de resposta padronizado ([ADR-0026](docs/adr/0026-envelope-de-resposta-padronizado.md), [ADR-0030](docs/adr/0030-envelope-v2.md)), Capability e Capability Registry ([ADR-0027](docs/adr/0027-capability-unidade-de-skill.md), [ADR-0033](docs/adr/0033-capability-registry.md)), execução por Intenção com decomposição em múltiplas Missions ([ADR-0028](docs/adr/0028-intencao-nao-comando.md)), SIGMA Language ([ADR-0032](docs/adr/0032-sigma-language.md), [SGL.md](SGL.md)), Digital Twin ([ADR-0035](docs/adr/0035-digital-twin.md), [DIGITAL_TWIN.md](DIGITAL_TWIN.md)), eventos em três camadas ([ADR-0034](docs/adr/0034-eventos-tres-camadas.md)), Autonomia Progressiva ([ADR-0029](docs/adr/0029-autonomia-progressiva.md)), princípio Declarativo-não-Imperativo ([ADR-0037](docs/adr/0037-declarativo-nao-imperativo.md)). Aprovada pelo Product Owner; push realizado.

## Release 2 — SIGMA Bootstrap ✅

Renomeada de "Kernel" — escopo reduzido ao bootstrap puro da plataforma, equivalente ao `Application` do Laravel/Spring Boot: Configuration Provider, Telemetry (Logs/Metrics/Tracing/Audit — não só Logger), DI Container, Modules (nunca Engines — ver nota abaixo), System Manifest, Lifecycle estendido (`discover → register → boot → start → ready → degraded → shutdown`), Health compatível com Kubernetes (`/health/live`, `/health/ready`, `/health/startup`), princípio de Self-Describing Components. **Fora do escopo**: Missions, IA/Agents, carregamento de Plugin real. Ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md), [ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md)–[ADR-0046](docs/adr/0046-self-describing-components.md) e [BOOTSTRAP.md](BOOTSTRAP.md)/[SYSTEM_MANIFEST.md](SYSTEM_MANIFEST.md). Proposta final (revisão 3) em [docs/releases/0002-sigma-bootstrap.md](docs/releases/0002-sigma-bootstrap.md), Decision Log e Validation Report completos. Primeiro código de aplicação do projeto — `packages/core`, `packages/kernel`, `services/event-bus`, `services/gateway`.

O schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role — [MULTITENANCY.md](MULTITENANCY.md)), antes bundlado nesta Release, não vive aqui nem no Memory Engine — tem Release própria, ver Release 3 — Identity Engine abaixo.

## Release 3 — Identity Engine ✅

Responde "quem sou, quem é o usuário, qual empresa, qual workspace, qual tenant, quais permissões, qual autonomia, qual contexto" — deliberadamente extraído do Memory Engine, por ser identidade e não memória (ver [ADR-0039](docs/adr/0039-identity-engine.md)). Contém o schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role, `tenant_id` obrigatório desde a primeira migration — [MULTITENANCY.md](MULTITENANCY.md), [ADR-0021](docs/adr/0021-multitenancy-desde-o-schema.md)) e a resolução do nível de Autonomia Progressiva por User/Role ([ADR-0029](docs/adr/0029-autonomia-progressiva.md)). Todo Engine seguinte consome o contexto de identidade resolvido por este Engine através do Kernel — nunca resolve Tenant/Workspace por conta própria.

Primeiro Engine a modelar domínio real, dividido em duas sub-Releases sequenciais ([ADR-0060](docs/adr/0060-release-dividida-em-sub-releases.md)): **3A — Identity Domain** ([docs/releases/0003a-identity-domain.md](docs/releases/0003a-identity-domain.md)) modelou o domínio em código puro, sem infraestrutura; **3B — Identity Infrastructure** ([docs/releases/0003b-identity-infrastructure.md](docs/releases/0003b-identity-infrastructure.md)) trouxe persistência (MariaDB), API (`services/auth`) e autenticação de verdade, validado via `docker compose up --build` real.

## Release 3.5 — Architecture Consolidation ✅

Não mudou o produto — fortaleceu a base antes da Memory Engine, seguindo a recomendação do Product Owner de que "é muito mais barato consolidar agora do que corrigir depois da Mission, Planner e Agent". Ver [docs/releases/0003.5-architecture-consolidation.md](docs/releases/0003.5-architecture-consolidation.md): [EVENT_CATALOG.md](EVENT_CATALOG.md), [VERSION.md](packages/identity-engine/VERSION.md) por Engine, [CHANGELOG.md](CHANGELOG.md) do produto, `CredentialProvider` substituindo `PasswordHasher`, validação cruzada de Contracts/ADRs/Decision Logs (encontrou e corrigiu duas divergências reais em `contracts/Identity.contract.yaml`), revisão de testes por camada, teste completo `bootstrap → login → workspace → logout` em ambiente Docker genuinamente limpo (`down -v` + `build --no-cache`).

## Release 4 — Memory Engine

**Segundo marco mais importante do projeto, depois da Foundation** — praticamente todo Engine seguinte (Mission, Intent, Planner, Agent, Council) depende da qualidade do que a Memory Engine expuser (avaliação do Product Owner, ver [ADR-0070](docs/adr/0070-roadmap-estendido-24-releases.md)). Modelagem e primeira persistência/consulta de Knowledge e Memory nos três níveis (ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md)), custódia dos primeiros Digital Twins (ver [DIGITAL_TWIN.md](DIGITAL_TWIN.md) — a versão madura vira Engine própria na Release 17). Primeira fonte real de Knowledge: o conteúdo já existente em [/knowledge](knowledge/). Construído antes do Mission Engine para que o Planner (Release 6) já tenha uma fonte de contexto/heurística ao nascer. Consome identidade/contexto da Release 3, não os resolve por conta própria.

Mesmo padrão de modelagem cuidadosa e divisão Domain-first da Release 3 ([ADR-0060](docs/adr/0060-release-dividida-em-sub-releases.md)): **4A — Memory Domain** ([docs/releases/0004a-memory-domain.md](docs/releases/0004a-memory-domain.md)) modela `MemoryRecord`/`KnowledgeRecord`/`DigitalTwin` em código puro, com a mecânica de promoção entre níveis ([ADR-0081](docs/adr/0081-mecanica-de-promocao-de-memory.md)) e a decisão de popular `UserTwin` desde já ([ADR-0079](docs/adr/0079-usertwin-desde-a-release-4.md)); **4B — Memory Infrastructure** ([docs/releases/0004b-memory-infrastructure.md](docs/releases/0004b-memory-infrastructure.md)) só começa depois que 4A estiver validada. Modelo completo em [MEMORY_MODEL.md](MEMORY_MODEL.md) e [MEMORY_LIFECYCLE.md](MEMORY_LIFECYCLE.md).

## Release 5 — Mission Engine

Entidade Mission, máquina de estados do ciclo de vida (ver [ARCHITECTURE.md §6](docs/architecture/ARCHITECTURE.md)), persistência, API REST mínima, eventos de domínio publicados no Event Bus. Suporta a cardinalidade Intent 1:N Mission desde o início (ver [ADR-0028](docs/adr/0028-intencao-nao-comando.md)), ainda que a Release 1:N completa só se materialize quando Planner (Release 6) e Intent (Release 7) existirem.

## Release 6 — Planner Engine

Recebe uma Intent (ainda estruturada manualmente/mockada — Intent Engine só nasce na Release 7, ver a nota de transparência em [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md)) e decompõe em uma ou mais Missions (Plan + Subtasks candidatas, Agent/Skill sugeridos). Primeira versão apoiada nos [Playbooks](playbooks/) já documentados como heurística inicial. Ver [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md) e [ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md) (por que Planner vem antes de Intent no desenvolvimento, mesmo a Intent vindo antes em runtime — numeração em confirmação, ver nota na tabela de Status acima).

## Release 7 — Intent Engine

Recebe linguagem natural ou evento estruturado e produz uma `Intent` real, substituindo as Intents mockadas usadas para construir a Release 6. Ver [ADR-0013](docs/adr/0013-intent-engine-como-porta-de-entrada.md).

## Release 8 — Skill Engine

Entidade Skill, mecanismo de descoberta e carregamento de Plugins (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) e [ADR-0017](docs/adr/0017-plugin-system.md)), Capabilities com autonomia por nível (ver [ADR-0027](docs/adr/0027-capability-unidade-de-skill.md) e [ADR-0029](docs/adr/0029-autonomia-progressiva.md)) implementadas de fato através de um primeiro Plugin ponta a ponta (candidato: `gestor`, já especificado em [plugins/gestor/manifest.json](plugins/gestor/manifest.json)). Primeiro ponto em que o SIGMA age de verdade sobre um sistema externo.

## Release 9 — Agent Engine

Entidades IA (provedor) e Agent (persona), contrato `AgentPort`. Primeira integração real com um provedor de IA (Claude, para o Agent de Engenharia — ver [agents/claude.md](agents/claude.md)). Uma Subtask passa a ser de fato delegada a um Agent real, respondendo no envelope padrão da Release 1.

## Release 10 — Execution Engine

Acompanhamento e validação da execução de Subtasks em andamento: retry, timeout, critério de sucesso/falha por tipo de Subtask.

## Release 11 — Audit Engine

Persistência estruturada de Events e Logs com correlação completa (Intent → Mission → Subtask → Agent → Skill), e a API/consulta necessária para auditoria e para alimentar interfaces. Candidato natural para absorver o componente estrutural **Observability** (Tracing/Metrics/Performance/Latency/Errors) sinalizado acima.

## Release 12 — Gateway/API

**Nova desde a extensão do roadmap (ADR-0070).** A superfície pública de API do SIGMA (REST/GraphQL) construída sobre `services/gateway` — infraestrutura que já existe desde a Release 2, mas hoje só expõe health-check. Aqui ganha rotas de domínio de verdade, para que a Release 13 (Interfaces) tenha o que consumir.

## Release 13 — Interfaces

React + TypeScript + Vite (PWA), Design System próprio, dark mode — dashboard de Missions em tempo real via WebSocket. React Native + Expo, mesmo Design System e mesmo backend, em seguida ou em paralelo conforme capacidade de execução no momento.

## Release 14 — Automation Engine

Motor de automação declarativa reagindo a eventos de domínio (ex: "quando uma Mission do tipo Novo Orçamento concluir, disparar a Mission de Nova Obra").

## Release 15 — Analytics

Métricas e indicadores sobre o que o SIGMA orquestra (tempo médio de Mission por tipo, taxa de falha por Skill, uso por Agent) — consome o histórico já registrado pelo Audit Engine.

## Release 16 — Knowledge Engine

**Nova desde a extensão do roadmap.** O "cérebro documental" do SIGMA — Clientes, Produtos, Playbooks, Empresa, Projetos, Normas, ADRs, Decisions, tudo indexado e consultável, não apenas arquivos estáticos em [/knowledge](knowledge/). Diferente da Memory Engine (Release 4, que responde "o que aconteceu/o que aprendi"), Knowledge Engine responde "o que a empresa sabe" — conhecimento curado, não experiência acumulada.

## Release 17 — Digital Twin

**Nova desde a extensão do roadmap.** [DIGITAL_TWIN.md](DIGITAL_TWIN.md) já existe como conceito desde a Release 1, com os primeiros Twins reais custodiados pela Memory Engine (Release 4). Esta Release matura o conceito em Engine própria — um modelo interno vivo do estado do mundo real (Cliente → Projetos → Reuniões → Chamados → Financeiro → Orçamentos → Relacionamentos), não apenas consulta a banco sob demanda.

## Release 18 — Capability Registry

**Nova desde a extensão do roadmap.** Evolução formal do [Plugin System](PLUGIN_SYSTEM.md): `Capability → Plugin → Agent → Permissões → Dependências`, formalizado como registro central para que o Planner saiba exatamente quem consegue executar cada ação antes de tentar. Candidato natural para absorver o componente estrutural **Policy Engine** sinalizado acima.

## Release 19 — Council Engine

**Nova desde a extensão do roadmap.** O [/council](council/) (hoje documentos de papéis — ProductOwner/CTO/LeadEngineer/Creative/Documentation) vira mecanismo executável de colaboração entre IAs (Claude/ChatGPT/Gemini/Manus/GitHub), com aprovação automática dentro de limites definidos pela Autonomia Progressiva — reduzindo a intermediação humana constante no fluxo de decisão entre especialistas.

## Release 20 — Multi-Agent Runtime

**Nova desde a extensão do roadmap.** Execução concorrente de múltiplos Agents (ex: Marketing, Financeiro, Jurídico, Comercial, Desenvolvimento simultaneamente), não mais um Agent de cada vez.

## Release 21 — Marketplace

**Nova desde a extensão do roadmap — escopo ainda não detalhado.**

## Release 22 — Cloud Sync

**Nova desde a extensão do roadmap — escopo ainda não detalhado.**

## Release 23 — Production Hardening

**Nova desde a extensão do roadmap — escopo ainda não detalhado.** Candidata a absorver os componentes estruturais **Secrets Manager** e possivelmente **Scheduler**/**Cache Layer** sinalizados acima.

## Release 24 — SIGMA v1.0

**Nova desde a extensão do roadmap.** Marco final desta fase do roadmap — escopo exato a definir quando a Release 23 estiver próxima de concluir.

---

Este roadmap é revisado ao final de cada Release concluída — a ordem e o escopo das Releases seguintes podem mudar com base no que for aprendido.
