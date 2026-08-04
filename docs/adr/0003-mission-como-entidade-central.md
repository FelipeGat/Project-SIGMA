# ADR-0003: Mission é o agregado raiz e a entidade central do sistema

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

SIGMA lida com 16 domínios de gestão (Projetos, Clientes, Empresas, Missões, Memória, Agentes, Skills, IA, Integrações, Usuários, Times, Conhecimento, Processos, Logs, Eventos, Automações). Sem uma entidade organizadora, esses domínios tendem a virar CRUDs desconectados — exatamente o que o SIGMA foi criado para não ser.

## Decisão

Mission é o agregado raiz do sistema. Toda ação relevante do SIGMA nasce de uma Missão e segue o ciclo: Interpretar → Planejar → Criar Plano → Criar Subtarefas → Escolher Skills → Executar → Validar → Registrar → Concluir. Nenhuma funcionalidade é aceita no domínio se não se conecta, direta ou indiretamente, a esse ciclo.

## Consequências

- Toda nova funcionalidade proposta deve ser posicionada no ciclo de vida da Missão antes de ser aceita — isso é o filtro de escopo do produto.
- Domínios de suporte (Knowledge, Memory, Skill, Agent, Automation) existem para servir Missões, não como módulos independentes com vida própria.
- O detalhamento do ciclo de vida (estados, eventos, transições) está em `docs/architecture/ARCHITECTURE.md` §5 e evolui junto com o Épico E1.
