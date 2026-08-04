# Project SIGMA

**Sistema Operacional Corporativo da Alfa Soluções.**

SIGMA não é um chatbot, não é um sistema CRUD e não é um assistente virtual. É a camada de orquestração — um Kernel e nove Engines especializados — que conecta pessoas, clientes, projetos, sistemas, inteligências artificiais e automações através de linguagem natural. Ver [MANIFESTO.md](MANIFESTO.md) para o porquê.

> Status atual: **Release 1 — SIGMA Protocol**, aprovada, push realizado (Release 0 — Foundation publicada em `github.com/FelipeGat/Project-SIGMA`). Proposta formal da **Release 2 — SIGMA Bootstrap** em preparação. Nenhum código de aplicação foi escrito ainda. Veja [ROADMAP.md](ROADMAP.md) e [memory/STATE.md](memory/STATE.md).

## Por onde começar

| Se você quer... | Leia |
|---|---|
| Entender **por que** o SIGMA existe (filosofia, não técnica) | [MANIFESTO.md](MANIFESTO.md) |
| Entender **como tudo conversa** — o contrato entre Engines, Skills e Agents | [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md) |
| Entender o que o SIGMA nunca deve virar | [VISION.md](VISION.md) |
| Entender o produto — quem usa, quem paga, personas | [PRODUCT.md](PRODUCT.md) |
| Entender o horizonte de longo prazo | [VISION_2030.md](VISION_2030.md) |
| Entender como o sistema é desenhado (Engines, domínio, stack) | [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) |
| Entender como o SIGMA inicia (boot, DI, ciclo de vida) | [BOOTSTRAP.md](BOOTSTRAP.md) |
| Entender o que pertence e o que nunca pertence ao Kernel | [KERNEL.md](KERNEL.md) |
| Entender como uma Skill vira código (Plugin System) | [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) |
| Entender a SIGMA Language (SGL) | [SGL.md](SGL.md) |
| Entender por que o SIGMA nunca lê um sistema externo direto | [DIGITAL_TWIN.md](DIGITAL_TWIN.md) |
| Entender a filosofia "tudo é evento" e as três camadas de evento | [EVENT_MODEL.md](EVENT_MODEL.md) |
| Entender observabilidade (Logs/Metrics/Tracing/Audit) | [TELEMETRY.md](TELEMETRY.md) |
| Entender Workspace e multiempresa | [WORKSPACES.md](WORKSPACES.md), [MULTITENANCY.md](MULTITENANCY.md) |
| Entender os três níveis de Memory | [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md) |
| Consultar o glossário de domínio | [DOMAIN.md](DOMAIN.md) |
| Ver quem governa o projeto e com que autoridade | [council/](council/) |
| Ver o que já foi decidido e por quê | [docs/adr/](docs/adr/) |
| Ver a proposta formal da próxima Release, para aprovação | [docs/releases/](docs/releases/) |
| Ver o que vem a seguir, por camada de Engine | [ROADMAP.md](ROADMAP.md) |
| Ver onde o projeto está agora | [memory/STATE.md](memory/STATE.md) |
| Contribuir com código ou documentação | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Conferir convenções de nomenclatura e código | [docs/conventions/](docs/conventions/) |

## Os quatro pilares

SIGMA é construído sobre quatro conceitos, e nenhuma funcionalidade nova deve ser desenhada fora deles:

- **Knowledge** — tudo que o sistema sabe.
- **Memory** — tudo que o sistema aprendeu (em três níveis: Operational, Project, Long Term).
- **Mission** — tudo que o sistema executa. É a entidade central: toda ação nasce de uma Mission.
- **Skill** — tudo que o sistema sabe fazer. Toda integração externa é uma Skill, implementada como Plugin, exposta como um conjunto de Capabilities.

SIGMA nunca executa comandos — executa **Intenções**. Um Intent Engine interpreta o objetivo, um Planner Engine decide o plano (podendo decompor em várias Missions relacionadas) — nunca a IA —, um Mission Engine acompanha, um Agent Engine delega a um especialista de IA, e um Skill Engine age através de Plugins, respeitando o nível de Autonomia Progressiva de quem pediu. Toda resposta volta no mesmo Envelope padronizado. Detalhes em [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md) e [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

## Estrutura do repositório

```
project-sigma/
├── apps/          # Superfícies de usuário: web, mobile, admin, telegram, cli
├── packages/       # Os nove Engines, core, design-system, sdk — bibliotecas
├── services/         # Processos deployáveis: gateway, auth, scheduler, notifications, ai-router, event-bus
├── plugins/           # Empacotamento técnico de cada Skill (manifest.json)
├── docs/               # Arquitetura, ADRs, convenções
├── tools/               # Ferramentas de desenvolvimento do monorepo
├── docker/               # Containers de ambiente local e deploy
├── agents/                # Documentação de cada Agent runtime (Claude, ChatGPT, Gemini, Manus)
├── skills/                 # Documentação de cada Skill/integração planejada
├── knowledge/               # Conhecimento de negócio da Alfa (não código, não docs do SIGMA)
├── playbooks/                 # Padrões documentados de Missions recorrentes
├── council/                     # Governança do projeto (Product Owner, CTO, Lead Engineer, Creative, Documentation)
└── memory/                        # Memória operacional do próprio projeto (estado, próximos passos, decisões)
```

Todas as pastas de código (`apps/`, `packages/`, `services/`, `plugins/`) estão vazias na Fase Foundation — apenas README/manifest de escopo. Ver [ADR-0016](docs/adr/0016-monorepo-apps-packages-services.md).

## Princípios inegociáveis

1. SOLID, Clean Architecture e DDD em todo módulo.
2. Arquitetura orientada a eventos (Event-Driven) como espinha dorsal de integração entre Engines — ver [EVENT_MODEL.md](EVENT_MODEL.md).
3. SIGMA nunca acessa o banco de dados de outro sistema. Toda comunicação é via API.
4. O Planner Engine decide o plano de uma Mission — nunca a IA/Agent.
5. Toda integração é um Plugin, carregado dinamicamente — o Kernel nunca conhece a implementação concreta.
6. Multiempresa desde o schema — Tenant/Company/Workspace/User/Role nunca retrofitados.
7. Observabilidade desde o dia zero — nenhum Engine roda sem Telemetry.
8. SIGMA executa Intenções, não comandos — uma Intent pode decompor em múltiplas Missions.
9. SIGMA é declarativo, nunca imperativo — descreve-se o estado desejado, o sistema decide o como.
10. Toda resposta do sistema — Skill, Plugin, Agent, canal externo — volta no mesmo Envelope padronizado.
11. Nenhuma solução provisória. Nenhum código sem arquitetura definida antes.
12. Desenvolvimento avança **uma Release por vez**, com aprovação explícita antes de iniciar implementação.

## Licença

Proprietário — © Alfa Soluções Tecnológicas. Veja [LICENSE](LICENSE).
