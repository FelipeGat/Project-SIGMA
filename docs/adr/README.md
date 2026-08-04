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
