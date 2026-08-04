# Architecture Decision Records

Registro das decisões arquiteturais do SIGMA — o quê foi decidido, o contexto que forçou a decisão, e as consequências aceitas conscientemente. Uma ADR não é revogada por reescrita; se uma decisão muda, uma nova ADR é criada referenciando a anterior como substituída.

Novas ADRs seguem o [template.md](template.md) e são numeradas sequencialmente.

| ADR | Título |
|---|---|
| [0001](0001-repositorio-proprio-e-independente.md) | SIGMA vive em repositório próprio, independente dos sistemas que orquestra |
| [0002](0002-estrutura-de-monorepo.md) | Backend, frontend web e mobile vivem no mesmo repositório (monorepo) |
| [0003](0003-mission-como-entidade-central.md) | Mission é o agregado raiz e a entidade central do sistema |
| [0004](0004-tres-camadas-ia-agente-skill.md) | Separação em três camadas — IA (provedor), Agente (persona), Skill (capacidade) |
| [0005](0005-sigma-nunca-executa-diretamente.md) | SIGMA nunca executa diretamente — atua somente como orquestrador |
| [0006](0006-integracao-externa-e-sempre-uma-skill.md) | Toda integração externa é modelada como Skill com contrato padronizado |
| [0007](0007-comunicacao-somente-via-api.md) | Comunicação exclusivamente via API — proibido acesso direto a banco de outros sistemas |
| [0008](0008-arquitetura-orientada-a-eventos.md) | Arquitetura orientada a eventos com Redis como backbone |
| [0009](0009-stack-tecnologica-de-referencia.md) | Stack tecnológica de referência |
| [0010](0010-processo-por-epicos-com-aprovacao.md) | Desenvolvimento avança por épicos únicos, com aprovação obrigatória antes de implementar |
| [0011](0011-arquitetura-em-camadas-de-engines.md) | Arquitetura em camadas de Engines especializados (Kernel, Intent, Planner, Mission, Memory, Agent, Skill, Execution, Audit) |
| [0012](0012-planner-decide-nunca-a-ia.md) | O Planner Engine decide o plano — nunca a IA/Agent |
| [0013](0013-intent-engine-como-porta-de-entrada.md) | Intent Engine como porta de entrada única de linguagem natural |
| [0014](0014-sigma-e-um-sistema-operacional-nao-uma-ia.md) | SIGMA é um Sistema Operacional, não uma IA |
| [0015](0015-roadmap-por-camadas-nao-por-feature.md) | Roadmap organizado por camada de Engine, não por feature |
| [0016](0016-monorepo-apps-packages-services.md) | Monorepo reorganizado em apps/packages/services/plugins/tools/docs/docker |
| [0017](0017-plugin-system.md) | Skills são implementadas como Plugins carregados dinamicamente — nunca compiladas no Kernel |
| [0018](0018-tudo-e-evento.md) | Tudo é Evento — o fluxo de Mission é modelado como sequência de eventos nomeados |
| [0019](0019-observabilidade-desde-o-dia-zero.md) | Observabilidade (Logs, Metrics, Tracing, Audit) desde o dia zero |
| [0020](0020-workspace-como-unidade-de-contexto.md) | Workspace como unidade de contexto operacional |
| [0021](0021-multitenancy-desde-o-schema.md) | Multiempresa (multi-tenant) desde o schema, nunca retrofitado |
| [0022](0022-memory-em-tres-niveis.md) | Memory organizada em três níveis — Operational, Project, Long Term |
| [0023](0023-governanca-via-council.md) | Governança do projeto formalizada em /council |
| [0024](0024-terminologia-release.md) | "Release" substitui "Sprint" como unidade de entrega |
| [0025](0025-protocol-antecede-kernel.md) | SIGMA Protocol é a Release 1 — antes do Kernel, antes de qualquer Engine |
| [0026](0026-envelope-de-resposta-padronizado.md) | Envelope de resposta padronizado para toda resposta do SIGMA |
| [0027](0027-capability-unidade-de-skill.md) | Capability é a unidade de implementação de uma Skill — não a função |
| [0028](0028-intencao-nao-comando.md) | SIGMA executa Intenções, não comandos — Intent pode decompor em múltiplas Missions |
| [0029](0029-autonomia-progressiva.md) | Princípio da Autonomia Progressiva — quatro níveis configuráveis |
| [0030](0030-envelope-v2.md) | Envelope v2 — correlationId, actor, intent, capability, audit |
| [0031](0031-ordem-runtime-vs-desenvolvimento.md) | Ordem de Runtime é distinta da Ordem de Desenvolvimento |
| [0032](0032-sigma-language.md) | SIGMA Language (SGL) como camada intermediária de representação de Intent |
| [0033](0033-capability-registry.md) | Capability Registry — versão, owner e dependências por Capability |
| [0034](0034-eventos-tres-camadas.md) | Eventos em três camadas — Technical, Semantic, Business |
| [0035](0035-digital-twin.md) | Digital Twin — SIGMA nunca lê um sistema externo diretamente |
| [0036](0036-objetivo-e-campo-da-intent.md) | "Objetivo" é o campo de propósito da Intent — não uma camada nova (proposto, aguardando confirmação) |
| [0037](0037-declarativo-nao-imperativo.md) | SIGMA é declarativo, nunca imperativo |
| [0038](0038-sigma-bootstrap-nao-kernel-completo.md) | Release 2 é o SIGMA Bootstrap — não o Kernel completo |
| [0039](0039-identity-engine.md) | Identity Engine — extraído do Memory Engine, Release própria |
| [0040](0040-bootstrap-nao-conhece-engines.md) | Bootstrap nunca conhece Engines — apenas Modules |
| [0041](0041-lifecycle-estendido.md) | Lifecycle estendido — discover, register, e o estado degraded |
| [0042](0042-health-estilo-kubernetes.md) | Health endpoints compatíveis com Kubernetes |
| [0043](0043-telemetry-desde-o-bootstrap.md) | Bootstrap inicializa Telemetry completa — não apenas um Logger |
| [0044](0044-configuration-provider.md) | Configuration Provider — cada Module declara sua própria configuração |
| [0045](0045-system-manifest.md) | System Manifest — o Bootstrap lê um único arquivo, o resto é descoberto |
| [0046](0046-self-describing-components.md) | Self-Describing Components |
| [0047](0047-decision-log-por-release.md) | Toda Release produz Código e Decision Log |
| [0048](0048-processo-quatro-fases.md) | Toda Release segue quatro fases — Proposal, Architecture Review, Implementation, Validation |
| [0049](0049-sigma-contracts.md) | Sigma Contracts — contrato formal por conceito de domínio |
| [0050](0050-compatibility-matrix.md) | Compatibility Matrix — COMPATIBILITY.md |
| [0051](0051-processo-rfc.md) | RFC — ideias antes da decisão |
| [0052](0052-kernel-api-apenas-interfaces.md) | Kernel API — apenas interfaces, nunca classes concretas |
| [0053](0053-escopo-restrito-release-2.md) | Escopo restrito da Release 2 — lista explícita do que existe e do que não existe |
| [0054](0054-tres-niveis-de-validacao.md) | Três níveis de validação obrigatórios por Release |
| [0055](0055-sdk-multi-linguagem.md) | `/sdk` multi-linguagem como diretório próprio, distinto de `packages/sdk` |
