# Domínio — SIGMA

Glossário da linguagem ubíqua do SIGMA: toda entidade de domínio, o que ela significa, quem a possui e como se relaciona com as demais. Este é o documento a consultar antes de nomear qualquer coisa nova no sistema — se um conceito não está aqui, ele não existe oficialmente no domínio ainda, mesmo que já apareça em conversa.

Convenção de nomenclatura correspondente: [docs/conventions/naming-conventions.md](docs/conventions/naming-conventions.md). Arquitetura dos Engines que operam sobre estas entidades: [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

## Núcleo de orquestração

### Intent
A interpretação estruturada de uma solicitação em linguagem natural (ou de um evento de outro sistema), produzida pelo **Intent Engine**. Uma Intent ainda não é um plano de ação — é a compreensão de *o que* está sendo pedido, antes de decidir *como* fazer.

### Plan
O plano de execução montado pelo **Planner Engine** a partir de uma Intent: quais Subtasks são necessárias, em que ordem, e quais Agentes/Skills são candidatos para cada uma. O Plan é decidido pelo sistema — nunca por uma IA agindo livremente. Ver [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md).

### Mission
A entidade central do sistema — o agregado raiz. Nasce de uma Intent já planejada (um Plan) e é gerenciada pelo **Mission Engine**: acompanha o progresso de cada Subtask até a conclusão. Toda ação relevante do SIGMA existe para servir ao ciclo de vida de uma Mission. Ver [ADR-0003](docs/adr/0003-mission-como-entidade-central.md).

### Subtask
Uma unidade de trabalho dentro do Plan de uma Mission, atribuída a um Agent específico.

### Knowledge
Tudo que o sistema sabe — base de conhecimento estruturada, documentação de domínio, contexto de negócio. Gerenciado pelo **Memory Engine**. Alimentado, entre outras fontes, por [/knowledge](knowledge/).

### Memory
Tudo que o sistema aprendeu — histórico de Missions concluídas, decisões passadas, padrões observados. Também gerenciado pelo **Memory Engine**, mas distinto de Knowledge: Knowledge é factual, Memory é experiencial.

### IA (AIProvider)
Um provedor/modelo bruto de inteligência artificial: credenciais, limites, custo, capacidades técnicas. Ex: Claude API, OpenAI API, Gemini API, Manus.

### Agent
Uma persona operacional especializada que usa uma IA para um tipo de trabalho. Gerenciado pelo **Agent Engine**, que decide qual Agent executa cada Subtask. Ver [/agents](agents/) e [ADR-0004](docs/adr/0004-tres-camadas-ia-agente-skill.md).

### Skill
Uma capacidade de ação concreta — geralmente uma integração com um sistema externo — invocável por um Agent através do **Skill Engine**. Ver [/skills](skills/) e [ADR-0006](docs/adr/0006-integracao-externa-e-sempre-uma-skill.md).

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

### User
Uma pessoa com acesso ao SIGMA — autor de Missions, membro de Teams.

### Team
Um agrupamento de Users, com escopo de permissão e visão sobre Missions.

### Company
Uma empresa do ecossistema Alfa (ou, futuramente, cliente white-label) em nome de quem o SIGMA opera.

## Negócio (orquestrado, não gerido pelo SIGMA)

Estas entidades representam conceitos de negócio que o SIGMA referencia para dar contexto a uma Mission — a fonte da verdade de cada uma permanece no sistema dono do domínio (ex: Gestor.Alfa), acessada via Skill. SIGMA nunca duplica esses dados como se fossem seus.

### Client
Um cliente do ecossistema Alfa. Fonte da verdade: Gestor.Alfa (ou equivalente).

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
Intent  →  Plan  →  Mission  →  Subtask  →  Agent  →  Skill  →  Integration
                        │                      │
                     Event/Log            IA (provedor)
                        │
                  Knowledge/Memory
```

Client, Contact, Project, Product, Budget, Document e Meeting não aparecem nesta cadeia como executores — eles são o *contexto de negócio* que uma Mission carrega, tipicamente obtido ou atualizado através de uma Skill (ex: `GestorSkill` lê/escreve Client e Budget no Gestor.Alfa).
