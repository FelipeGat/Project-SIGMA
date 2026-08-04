# SIGMA

**Sistema Operacional Corporativo da Alfa Soluções.**

SIGMA não é um chatbot, não é um sistema CRUD e não é um assistente virtual. É a camada de orquestração que conecta pessoas, clientes, projetos, sistemas, inteligências artificiais e automações através de linguagem natural — o núcleo operacional da empresa.

> Status atual: **Fase Foundation** (Sprint 0). Nenhum código de aplicação foi escrito ainda — apenas arquitetura, documentação e estrutura de projeto. Veja [ROADMAP.md](ROADMAP.md).

## Por onde começar

| Se você quer... | Leia |
|---|---|
| Entender por que o SIGMA existe e o que ele nunca deve virar | [VISION.md](VISION.md) |
| Entender como o sistema é desenhado (domínio, módulos, stack) | [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) |
| Ver o que já foi decidido e por quê | [docs/adr/](docs/adr/) |
| Ver o que vem a seguir, por épico | [ROADMAP.md](ROADMAP.md) |
| Contribuir com código ou documentação | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Conferir convenções de nomenclatura e código | [docs/conventions/](docs/conventions/) |

## Os quatro pilares

SIGMA é construído sobre quatro conceitos, e nenhuma funcionalidade nova deve ser desenhada fora deles:

- **Knowledge** — tudo que o sistema sabe.
- **Memory** — tudo que o sistema aprendeu.
- **Mission** — tudo que o sistema executa. É a entidade central: toda ação nasce de uma Missão.
- **Skill** — tudo que o sistema sabe fazer. Toda integração externa é uma Skill.

SIGMA nunca executa uma ação diretamente — ele **orquestra** Agentes (especialistas de IA) que usam Skills (integrações) para cumprir Missões. Detalhes em [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

## Estrutura do repositório

```
Sigma-IO/
├── backend/           # Laravel 12 / PHP 8.4 — API, domínio, orquestração (vazio na Fase Foundation)
├── frontend-web/       # React + TypeScript + Vite (PWA) — painel de operação (vazio na Fase Foundation)
├── frontend-mobile/    # React Native + Expo — mesmo Design System, mesmo backend (vazio na Fase Foundation)
└── docs/               # Arquitetura, ADRs, convenções, domínio
```

## Princípios inegociáveis

1. SOLID, Clean Architecture e DDD em todo módulo.
2. Arquitetura orientada a eventos (Event-Driven) como espinha dorsal de integração entre contextos.
3. SIGMA nunca acessa o banco de dados de outro sistema. Toda comunicação é via API.
4. Nenhuma solução provisória. Nenhum código sem arquitetura definida antes.
5. Desenvolvimento avança **um épico por vez**, com aprovação explícita antes de iniciar implementação.

## Licença

Proprietário — © Alfa Soluções Tecnológicas. Veja [LICENSE](LICENSE).
