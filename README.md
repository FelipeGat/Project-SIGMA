# Project SIGMA

**Sistema Operacional Corporativo da Alfa Soluções.**

SIGMA não é um chatbot, não é um sistema CRUD e não é um assistente virtual. É a camada de orquestração — um Kernel e nove Engines especializados — que conecta pessoas, clientes, projetos, sistemas, inteligências artificiais e automações através de linguagem natural. Ver [MANIFESTO.md](MANIFESTO.md) para o porquê.

> Status atual: **Fase Foundation** (Sprint 0.1). Nenhum código de aplicação foi escrito ainda — apenas arquitetura, documentação e estrutura de projeto. Veja [ROADMAP.md](ROADMAP.md) e [memory/STATE.md](memory/STATE.md).

## Por onde começar

| Se você quer... | Leia |
|---|---|
| Entender **por que** o SIGMA existe (filosofia, não técnica) | [MANIFESTO.md](MANIFESTO.md) |
| Entender o que o SIGMA nunca deve virar | [VISION.md](VISION.md) |
| Entender o produto — quem usa, quem paga, personas | [PRODUCT.md](PRODUCT.md) |
| Entender o horizonte de longo prazo | [VISION_2030.md](VISION_2030.md) |
| Entender como o sistema é desenhado (Engines, domínio, stack) | [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) |
| Consultar o glossário de domínio | [DOMAIN.md](DOMAIN.md) |
| Ver o que já foi decidido e por quê | [docs/adr/](docs/adr/) |
| Ver o que vem a seguir, por camada de Engine | [ROADMAP.md](ROADMAP.md) |
| Ver onde o projeto está agora | [memory/STATE.md](memory/STATE.md) |
| Contribuir com código ou documentação | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Conferir convenções de nomenclatura e código | [docs/conventions/](docs/conventions/) |

## Os quatro pilares

SIGMA é construído sobre quatro conceitos, e nenhuma funcionalidade nova deve ser desenhada fora deles:

- **Knowledge** — tudo que o sistema sabe.
- **Memory** — tudo que o sistema aprendeu.
- **Mission** — tudo que o sistema executa. É a entidade central: toda ação nasce de uma Mission.
- **Skill** — tudo que o sistema sabe fazer. Toda integração externa é uma Skill.

SIGMA nunca executa uma ação diretamente. Um Intent Engine interpreta o pedido, um Planner Engine decide o plano — nunca a IA —, um Mission Engine acompanha, um Agent Engine delega a um especialista de IA, e um Skill Engine age através de integrações. Detalhes em [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

## Estrutura do repositório

```
project-sigma/
├── backend/           # Laravel 12 / PHP 8.4 — Kernel e Engines (vazio na Fase Foundation)
├── frontend-web/       # React + TypeScript + Vite (PWA) — painel de operação (vazio na Fase Foundation)
├── frontend-mobile/    # React Native + Expo — mesmo Design System, mesmo backend (vazio na Fase Foundation)
├── docs/               # Arquitetura, ADRs, convenções
├── agents/             # Documentação de cada Agent (Claude, ChatGPT, Gemini, Manus)
├── skills/             # Documentação de cada Skill/integração planejada
├── knowledge/          # Conhecimento de negócio da Alfa (não código, não docs do SIGMA)
├── playbooks/          # Padrões documentados de Missions recorrentes
└── memory/             # Memória operacional do próprio projeto (estado, próximos passos, decisões)
```

## Princípios inegociáveis

1. SOLID, Clean Architecture e DDD em todo módulo.
2. Arquitetura orientada a eventos (Event-Driven) como espinha dorsal de integração entre Engines.
3. SIGMA nunca acessa o banco de dados de outro sistema. Toda comunicação é via API.
4. O Planner Engine decide o plano de uma Mission — nunca a IA/Agent.
5. Nenhuma solução provisória. Nenhum código sem arquitetura definida antes.
6. Desenvolvimento avança **um épico por vez**, com aprovação explícita antes de iniciar implementação.

## Licença

Proprietário — © Alfa Soluções Tecnológicas. Veja [LICENSE](LICENSE).
