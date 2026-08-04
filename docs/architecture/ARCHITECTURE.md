# Arquitetura de Alto Nível — SIGMA

Este documento descreve como o SIGMA é estruturado: os Engines que compõem seu núcleo, o modelo de domínio, o ciclo de vida da Mission, o contrato de Skill, o modelo de Agentes, e a stack de referência. É o documento a se manter atualizado conforme decisões evoluem; decisões pontuais e seu porquê ficam registradas em [docs/adr/](../adr/).

## 0. SIGMA é um Sistema Operacional, não uma IA

Antes de qualquer diagrama: o SIGMA não é um chat inteligente com plugins. É uma plataforma operacional corporativa, com um Kernel e Engines de responsabilidade única — cada um resolvendo um problema específico do ciclo de vida de uma Mission. IA é uma capacidade que alguns desses Engines usam (Agent Engine, ao delegar uma Subtask), não a identidade do sistema como um todo. Ver [MANIFESTO.md](../../MANIFESTO.md) e [ADR-0014](../adr/0014-sigma-e-um-sistema-operacional-nao-uma-ia.md).

## 1. Princípios

- **DDD** — o domínio (Mission, Skill, Agent, Knowledge, Memory...) é modelado primeiro, isolado de framework e infraestrutura.
- **Clean Architecture** — dependências apontam para dentro: domínio não conhece infraestrutura; infraestrutura implementa contratos do domínio.
- **Event-Driven** — Engines não se chamam diretamente; comunicam-se por eventos de domínio.
- **SOLID**, alta coesão, baixo acoplamento — em cada módulo e entre módulos.
- **Desacoplamento físico** — SIGMA nunca acessa o banco de dados de outro sistema (Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam...). Toda comunicação com sistemas externos é via API. Ver [ADR-0007](../adr/0007-comunicacao-somente-via-api.md).
- **O Planner decide, a IA executa** — nenhum Agent decide o que uma Mission deve fazer; ele executa a Subtask que o Planner Engine já definiu. Ver [ADR-0012](../adr/0012-planner-decide-nunca-a-ia.md).

## 2. Os nove Engines

O núcleo do SIGMA é composto por nove Engines, cada um com responsabilidade única. Ver [ADR-0011](../adr/0011-arquitetura-em-camadas-de-engines.md).

| # | Engine | Responsabilidade |
|---|---|---|
| 0 | **Kernel** | Ciclo de vida da plataforma: bootstrap, configuração, contexto de execução, health |
| 1 | **Intent Engine** | Interpreta linguagem natural/eventos em uma `Intent` estruturada |
| 2 | **Planner Engine** | Monta o `Plan` de execução a partir de uma Intent — decide, nunca a IA |
| 3 | **Mission Engine** | Gerencia a `Mission` e seu progresso a partir de um Plan |
| 4 | **Memory Engine** | Organiza `Knowledge` (o que se sabe) e `Memory` (o que se aprendeu) |
| 5 | **Agent Engine** | Decide qual `Agent` executa cada `Subtask` |
| 6 | **Skill Engine** | Conversa com sistemas externos através de `Skills` |
| 7 | **Execution Engine** | Acompanha e valida a execução de cada Subtask em andamento |
| 8 | **Audit Engine** | Registra `Events` e `Logs` — rastreabilidade de tudo que acontece |

Interfaces (painel Web, App Mobile), **Automation Engine** e **Analytics** consomem estes nove Engines através de eventos e API — não fazem parte do núcleo de orquestração.

## 3. Visão geral do sistema

```mermaid
graph TB
    User["Usuário / Sistema externo"] -->|"linguagem natural / evento"| Intent["Intent Engine"]
    Intent -->|"Intent estruturada"| Planner["Planner Engine"]
    Planner -->|"Plan (Subtasks)"| Mission["Mission Engine"]
    Mission -->|"delega Subtask"| AgentEng["Agent Engine"]
    AgentEng --> Agent1["Agent — Engenharia (Claude)"]
    AgentEng --> Agent2["Agent — Estratégia (ChatGPT)"]
    AgentEng --> Agent3["Agent — Design (Gemini)"]
    AgentEng --> Agent4["Agent — Documentação (Manus)"]
    Agent1 -->|"usa"| SkillEng["Skill Engine"]
    Agent2 -->|"usa"| SkillEng
    Agent3 -->|"usa"| SkillEng
    Agent4 -->|"usa"| SkillEng
    SkillEng --> S1["GestorSkill"]
    SkillEng --> S2["WhatsAppSkill"]
    SkillEng --> S3["GitHubSkill"]
    SkillEng --> S4["..."]
    S1 -.->|"API"| Ext1["Gestor.Alfa"]
    S2 -.->|"API"| Ext2["WhatsApp Cloud API"]
    S3 -.->|"API"| Ext3["GitHub"]
    Mission --> Exec["Execution Engine"]
    Exec -->|"valida / retry"| Mission
    Mission --> EventBus["Event Bus (Redis)"]
    EventBus --> Audit["Audit Engine"]
    EventBus --> Memory["Memory Engine"]
    EventBus --> Automation["Automation Engine"]
    EventBus --> WS["WebSocket"]
    WS --> Web["Painel Web (React PWA)"]
    WS --> Mobile["App Mobile (React Native)"]
    Kernel["Kernel"] -.->|"config / bootstrap"| Intent
    Kernel -.-> Planner
    Kernel -.-> Mission
```

SIGMA nunca fala diretamente com um sistema externo, e nenhum Engine decide fora do seu papel: **Intent Engine interpreta → Planner Engine decide → Mission Engine acompanha → Agent Engine delega → Skill Engine age → Execution Engine valida → Audit Engine registra.** Essa cadeia é o que permite trocar um provedor de IA ou uma integração sem tocar no núcleo, e auditar exatamente onde uma Mission está a qualquer momento.

## 4. As três camadas de execução (dentro do Agent Engine)

Um erro comum em sistemas de orquestração de IA é misturar "o modelo" com "quem o usa" e com "o que ele pode fazer". SIGMA separa isso em três conceitos distintos, geridos pelo Agent Engine e pelo Skill Engine:

| Camada | Entidade | Responsabilidade | Exemplo |
|---|---|---|---|
| Provedor | **IA** | Credenciais, limites, custo, capacidades técnicas de um modelo | Claude API, OpenAI API, Gemini API, Manus |
| Persona operacional | **Agent** | Uma especialidade que usa uma IA para um tipo de trabalho | Agent de Engenharia (usa Claude), Agent de Estratégia (usa ChatGPT) |
| Capacidade de ação | **Skill** | Uma integração concreta que um Agent invoca para agir no mundo | GitHubSkill, WhatsAppSkill, GestorSkill |

Um Agent nunca acessa uma API externa diretamente — ele solicita ao Skill Engine. Ver [ADR-0004](../adr/0004-tres-camadas-ia-agente-skill.md) e a documentação de cada Agent em [/agents](../../agents/).

### Especialidades de Agent (conjunto inicial)

| Agent | IA | Especialidade | Documentação |
|---|---|---|---|
| Agent de Engenharia | Claude | Engenharia de Software | [agents/claude.md](../../agents/claude.md) |
| Agent de Estratégia | ChatGPT | Estratégia | [agents/chatgpt.md](../../agents/chatgpt.md) |
| Agent de Design | Gemini | Design | [agents/gemini.md](../../agents/gemini.md) |
| Agent de Documentação | Manus | Documentação | [agents/manus.md](../../agents/manus.md) |

Novas IAs e Agents serão adicionados por configuração, não por alteração de domínio.

## 5. Modelo de domínio

Ver o glossário completo em [DOMAIN.md](../../DOMAIN.md). Os domínios se agrupam em contextos delimitados (bounded contexts), cada um primariamente servido por um ou mais Engines:

| Contexto | Entidades | Engine principal |
|---|---|---|
| **Interpretação** | Intent | Intent Engine |
| **Planejamento** | Plan, Subtask (candidatos) | Planner Engine |
| **Missão** (núcleo) | Mission, Subtask (em execução) | Mission Engine |
| **Conhecimento** | Knowledge, Memory | Memory Engine |
| **Capacidade** | Agent, IA | Agent Engine |
| **Integração** | Skill, Integration | Skill Engine |
| **Rastreabilidade** | Event, Log | Audit Engine |
| **Identidade & Organização** | User, Team, Company | (contexto de apoio, sem Engine dedicado) |
| **Negócio** | Client, Contact, Project, Product, Budget, Document, Meeting | (referenciado via Skill, fonte da verdade externa) |
| **Processo** | Process, Automation | Automation Engine |

Contextos se comunicam exclusivamente por eventos de domínio publicados no Event Bus — nunca por chamada direta entre módulos. O detalhamento de cada entidade (atributos, invariantes, relacionamentos) é expandido épico a épico conforme cada Engine é implementado — modelar em detalhe antes do épico correspondente ser aprovado gera documentação que se torna fictícia.

## 6. Mission — ciclo de vida através dos Engines

Uma Mission nasce de uma Intent já planejada e é gerenciada pelo Mission Engine até sua conclusão.

```mermaid
stateDiagram-v2
    [*] --> Recebida
    Recebida --> Interpretando: Intent Engine
    Interpretando --> Planejando: Intent estruturada → Planner Engine
    Interpretando --> Rejeitada: intenção ambígua/inválida
    Planejando --> SubtarefasCriadas: Plan pronto → Mission Engine cria a Mission
    SubtarefasCriadas --> EmExecucao: Agent Engine delega cada Subtask
    EmExecucao --> Validando: Execution Engine acompanha
    Validando --> EmExecucao: validação falhou, retry
    Validando --> Registrada: validação ok → Audit Engine
    Validando --> Falhou: falha definitiva
    Registrada --> Concluida
    Falhou --> [*]
    Rejeitada --> [*]
    Concluida --> [*]
    EmExecucao --> Cancelada: cancelamento manual
    Cancelada --> [*]
```

Cada transição de estado publica um evento de domínio (`mission.interpreted`, `mission.planned`, `subtasks.created`, `mission.executing`, `mission.validated`, `mission.completed`, `mission.failed`...) consumido pelo Audit Engine (rastreabilidade), Memory Engine (aprendizado) e WebSocket (tempo real no painel).

## 7. Skill — contrato

Toda integração externa é modelada como Skill, operada pelo Skill Engine. Uma Skill é desacoplada e possui, obrigatoriamente:

- **Configuração** — como a Skill é ativada e parametrizada por empresa/ambiente.
- **Permissões** — quem (qual Agent, qual Mission) pode invocá-la e para quê.
- **Entrada** — contrato de dados que a Skill aceita.
- **Saída** — contrato de dados que a Skill devolve.
- **Eventos** — o que a Skill publica no Event Bus ao ser executada (sucesso, falha, progresso).
- **Logs** — toda invocação é registrada, correlacionada à Mission que a originou.
- **Testes** — contrato coberto por testes automatizados antes de ir ao ar.
- **Documentação** — o que a Skill faz, como configurá-la, exemplos de uso.

Contrato de referência (assinatura, não implementação — a implementação nasce com o épico que entrega a primeira Skill real):

```php
interface Skill
{
    public function name(): string;
    public function configure(SkillConfig $config): void;
    public function authorize(Agent $agent, Mission $mission): bool;
    public function execute(SkillInput $input): SkillOutput;
}
```

Skills previstas, documentadas em [/skills](../../skills/): `GestorSkill`, `GitHubSkill`, `TelegramSkill`, `GoogleCalendarSkill`, `EmailSkill` — e futuramente `DockerSkill`, `WhatsAppSkill`, entre outras.

## 8. Estrutura de módulo (backend)

Cada Engine e cada contexto de apoio do backend Laravel segue a mesma estrutura interna, isolando domínio de infraestrutura:

```
backend/app/Modules/<Engine-ou-Contexto>/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Repositories/          # contratos (interfaces)
├── Application/
│   ├── Actions/
│   ├── Services/
│   └── DTOs/
├── Infrastructure/
│   ├── Repositories/           # implementações (Eloquent)
│   ├── Listeners/
│   └── Observers/
└── Presentation/
    ├── Controllers/
    ├── Policies/
    └── Resources/
```

Ex.: `backend/app/Modules/Mission/`, `backend/app/Modules/Planner/`, `backend/app/Modules/Skill/`. Esta estrutura é o padrão a ser usado a partir do primeiro épico de implementação; nenhum código é criado na Fase Foundation.

## 9. Stack de referência

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12, PHP 8.4, DDD, Event-Driven, Redis, MariaDB, API REST, WebSocket, Queues, Scheduler |
| Frontend Web | React, TypeScript, Vite, PWA, Design System próprio, Dark Mode, Offline, Responsivo |
| Mobile | React Native, Expo — mesmo Design System, mesmo Backend |
| Infraestrutura de eventos | Redis (filas, pub/sub, broadcasting) |

Justificativa e alternativas consideradas em [ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md).

## 10. Regras de desacoplamento

1. SIGMA nunca acessa diretamente o banco de dados de outro sistema Alfa. Toda leitura e escrita em Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam etc. acontece através da API pública desses sistemas, encapsulada numa Skill operada pelo Skill Engine.
2. Um Engine nunca chama outro Engine diretamente — apenas via eventos publicados no Event Bus, exceto a cadeia síncrona explícita Intent → Planner → Mission → Agent (delegação de Subtask), que é o próprio fluxo de orquestração.
3. Um Agent nunca invoca uma API externa diretamente — sempre através do Skill Engine.
4. Nenhuma Skill mantém estado de negócio próprio além do necessário para operar (idempotência, retry) — a fonte da verdade de cada domínio de negócio permanece no sistema dono desse domínio.
5. O Planner Engine decide o Plan; nenhum Agent decide, por conta própria, o que uma Mission deve fazer. Ver [ADR-0012](../adr/0012-planner-decide-nunca-a-ia.md).

Ver também [ADR-0007](../adr/0007-comunicacao-somente-via-api.md).
