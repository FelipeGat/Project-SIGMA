# Domínio — SIGMA

Glossário da linguagem ubíqua do SIGMA: toda entidade de domínio, o que ela significa, quem a possui e como se relaciona com as demais. Este é o documento a consultar antes de nomear qualquer coisa nova no sistema — se um conceito não está aqui, ele não existe oficialmente no domínio ainda, mesmo que já apareça em conversa.

Convenção de nomenclatura correspondente: [docs/conventions/naming-conventions.md](docs/conventions/naming-conventions.md). Arquitetura dos Engines que operam sobre estas entidades: [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

## Núcleo de orquestração

### Intent
A interpretação estruturada de um **objetivo**, não de uma lista de comandos — produzida pelo **Intent Engine** a partir de linguagem natural ou de um evento de outro sistema. Carrega um campo `objective`: a frase-resumo, declarativa (um estado desejado, não um passo a passo — ver [ADR-0037](docs/adr/0037-declarativo-nao-imperativo.md)), do que se quer alcançar — "Objetivo" é o nome de produto, em português, para esse campo (ver [ADR-0036](docs/adr/0036-objetivo-e-campo-da-intent.md), proposto e aguardando confirmação). Uma Intent pode se decompor em **uma ou mais Missions** relacionadas (cardinalidade 1:N — ver [SIGMA_PROTOCOL.md §2](SIGMA_PROTOCOL.md#2-intenção-não-comando) e [ADR-0028](docs/adr/0028-intencao-nao-comando.md)). Representada internamente em [SGL](SGL.md) antes de virar o Envelope. Uma Intent ainda não é um plano de ação — é a compreensão de *o que* se quer alcançar, antes de decidir *como*.

### Plan
O plano de execução montado pelo **Planner Engine** a partir de uma Intent: quantas Missions são necessárias, quais Subtasks cada uma exige, em que ordem, e quais Agentes/Skills/Capabilities são candidatos para cada uma. O Plan é decidido pelo sistema — nunca por uma IA agindo livremente. Ver [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md).

### Mission
A entidade central do sistema — o agregado raiz. Nasce de uma Intent já planejada (uma entre potencialmente várias Missions do mesmo Plan) e é gerenciada pelo **Mission Engine**: acompanha o progresso de cada Subtask até a conclusão. Toda ação relevante do SIGMA existe para servir ao ciclo de vida de uma Mission. Ver [ADR-0003](docs/adr/0003-mission-como-entidade-central.md).

### Subtask
Uma unidade de trabalho dentro do Plan de uma Mission, atribuída a um Agent específico, que invoca uma Capability para cumpri-la.

### Capability
Uma ação nomeada e discreta que uma Skill implementa (ex: `CreateEvent`), com versão própria, owner, schema de entrada/saída, dependências de outras Capabilities, e um nível mínimo de autonomia requerido. Uma Skill é um conjunto de Capabilities — nunca funções soltas. O conjunto de todas as Capabilities de todos os Plugins carregados forma o **Capability Registry**, mantido pelo Skill Engine. Ver [SIGMA_PROTOCOL.md §4](SIGMA_PROTOCOL.md#4-capability-e-capability-registry) e [ADR-0027](docs/adr/0027-capability-unidade-de-skill.md)/[ADR-0033](docs/adr/0033-capability-registry.md).

### Knowledge
Tudo que o sistema sabe — base de conhecimento estruturada, documentação de domínio, contexto de negócio. Gerenciado pelo **Memory Engine**. Alimentado, entre outras fontes, por [/knowledge](knowledge/).

### Memory
Tudo que o sistema aprendeu — histórico de Missions concluídas, decisões passadas, padrões observados. Também gerenciado pelo **Memory Engine**, mas distinto de Knowledge: Knowledge é factual, Memory é experiencial. Organizada em três níveis — Operational, Project, Long Term — ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md) e [ADR-0022](docs/adr/0022-memory-em-tres-niveis.md).

### IA (AIProvider)
Um provedor/modelo bruto de inteligência artificial: credenciais, limites, custo, capacidades técnicas. Ex: Claude API, OpenAI API, Gemini API, Manus.

### Agent
Uma persona operacional especializada que usa uma IA para um tipo de trabalho. Gerenciado pelo **Agent Engine**, que decide qual Agent executa cada Subtask. Ver [/agents](agents/) e [ADR-0004](docs/adr/0004-tres-camadas-ia-agente-skill.md).

### Skill
Uma capacidade de ação concreta — geralmente uma integração com um sistema externo — invocável por um Agent através do **Skill Engine**. Ver [/skills](skills/) e [ADR-0006](docs/adr/0006-integracao-externa-e-sempre-uma-skill.md). Toda Skill é implementada tecnicamente como um **Plugin** carregado dinamicamente — ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) e [ADR-0017](docs/adr/0017-plugin-system.md); o Kernel nunca conhece a implementação concreta.

### Integration
Metadados e configuração de uma integração concreta usada por uma Skill (credenciais, endpoint, ambiente). Uma Skill pode usar mais de uma Integration; uma Integration não é, por si só, invocável — só através da Skill que a encapsula.

### Event
Um fato de domínio publicado no Event Bus quando algo relevante acontece (`MissionCompleted`, `SkillInvoked`...). Registrado pelo **Audit Engine**.

### Log
Um registro de execução/auditoria — a evidência de que algo aconteceu, correlacionada à Mission, Agent e Skill de origem. Também de responsabilidade do **Audit Engine**.

### Automation
Uma regra declarativa que reage a Events para disparar novas Missions ou ações, sem intervenção humana.

### Process
Um fluxo ou procedimento reutilizável que uma Mission pode seguir — a versão formal, executável, do que um [Playbook](playbooks/) captura primeiro como documentação.

## Identidade & Organização

Hierarquia completa de isolamento e contexto, gerenciada pelo **Identity Engine** — ver [MULTITENANCY.md](MULTITENANCY.md), [WORKSPACES.md](WORKSPACES.md) e [ADR-0039](docs/adr/0039-identity-engine.md). Distinto do Memory Engine: Identity responde "quem", Memory responde "o que sei/aprendi".

### Tenant
Fronteira de isolamento total de dados — o nível mais alto da hierarquia. Hoje existe um único Tenant real ("Alfa Soluções"); a modelagem já suporta mais de um desde o schema. Ver [ADR-0021](docs/adr/0021-multitenancy-desde-o-schema.md).

### Company
Uma empresa dentro de um Tenant (GW, Delta, Invest — as empresas do Grupo Soluções) em nome de quem o SIGMA opera.

### Workspace
A unidade de contexto operacional dentro de uma Company — agrupa Client/Project/Budget/Meeting/Document relacionados automaticamente (ex: "Cliente Brenno"). Ver [WORKSPACES.md](WORKSPACES.md) e [ADR-0020](docs/adr/0020-workspace-como-unidade-de-contexto.md).

### User
Uma pessoa com acesso ao SIGMA, associada a um Tenant — autor de Missions, membro de Teams e de um ou mais Workspaces.

### Team
Um agrupamento de Users, com escopo de permissão e visão sobre Missions.

### Role
Um conjunto de permissões, aplicável no nível Tenant, Company ou Workspace (ex: "Comercial", "Técnico", "Administrativo"). Carrega, entre outras permissões, um **nível de Autonomia Progressiva** (0–3) que limita o quanto o SIGMA pode agir em nome de um User com esse Role sem confirmação humana. Ver [SIGMA_PROTOCOL.md §4](SIGMA_PROTOCOL.md#4-autonomia-progressiva) e [ADR-0029](docs/adr/0029-autonomia-progressiva.md).

## Negócio (orquestrado, não gerido pelo SIGMA)

Estas entidades representam conceitos de negócio que o SIGMA referencia para dar contexto a uma Mission — a fonte da verdade de cada uma permanece no sistema dono do domínio (ex: Gestor.Alfa), acessada via Skill. SIGMA nunca duplica esses dados como se fossem seus, mas mantém uma representação viva de cada uma para leitura rápida — ver **Digital Twin** abaixo.

### Digital Twin
A representação viva e sincronizada de um Client, Project, Company ou User, mantida pelo SIGMA (custódia do Memory Engine) e atualizada a partir de Semantic Events. Toda leitura de contexto consulta o Twin; toda escrita continua indo direto ao sistema externo via Capability — o Twin nunca é a fonte da verdade. Ver [DIGITAL_TWIN.md](DIGITAL_TWIN.md) e [ADR-0035](docs/adr/0035-digital-twin.md).

### Client
Um cliente do ecossistema Alfa. Fonte da verdade: Gestor.Alfa (ou equivalente); representado no SIGMA por um `ClientTwin`.

### Contact
Uma pessoa de contato associada a um Client.

### Project
Um projeto ou obra em andamento para um Client.

### Product
Um item/produto ou serviço comercializado pela Alfa, referenciado por um Orçamento.

### Budget (Orçamento)
Uma proposta comercial associada a um Client/Project. Fonte da verdade: Gestor.Alfa.

### Document
Um documento (proposta, relatório, contrato, ata) gerado ou referenciado no curso de uma Mission.

### Meeting (Reunião)
Um compromisso — presencial ou remoto — que pode ser tanto o gatilho de uma Mission ("Sigma, participe da reunião do cliente Brenno") quanto uma Subtask dentro dela.

## Relação entre as camadas

```
                          ┌── Mission 1 ──┐
Intent  →  Plan  ─────────┼── Mission 2 ──┼──→  Subtask  →  Agent  →  Capability (Skill)  →  Integration
 (1)        (1)           └── Mission N ──┘        │             │            │
                                  │              Event/Log   IA (provedor)  autonomy_level_required
                                  │
                            Knowledge/Memory
```

Um Plan pode decidir por mais de uma Mission a partir de uma única Intent (cardinalidade 1:N — ver [ADR-0028](docs/adr/0028-intencao-nao-comando.md)); todas rastreáveis à mesma Intent de origem. Client, Contact, Project, Product, Budget, Document e Meeting não aparecem nesta cadeia como executores — eles são o *contexto de negócio* que uma Mission carrega, tipicamente obtido ou atualizado através de uma Capability (ex: `GestorSkill.UpdateBudget` lê/escreve Client e Budget no Gestor.Alfa). Toda resposta ao longo desta cadeia é normalizada no [Envelope do SIGMA Protocol](SIGMA_PROTOCOL.md#1-o-envelope).
