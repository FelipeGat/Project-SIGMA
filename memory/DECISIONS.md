# Log de decisões

Registro cronológico e leve de decisões do projeto — inclusive as que não justificam uma ADR própria. Decisões arquiteturais formais têm entrada aqui apontando para a ADR correspondente.

## 2026-08-04

- **SIGMA nasce como repositório próprio, desacoplado dos sistemas que orquestra.** → [ADR-0001](../docs/adr/0001-repositorio-proprio-e-independente.md)
- **Monorepo** (`backend/`, `frontend-web/`, `frontend-mobile/`, `docs/`) em vez de polyrepo, por ora. → [ADR-0002](../docs/adr/0002-estrutura-de-monorepo.md)
- **Mission é o agregado raiz** do domínio; toda funcionalidade se conecta ao seu ciclo de vida. → [ADR-0003](../docs/adr/0003-mission-como-entidade-central.md)
- **Três camadas de execução**: IA (provedor) → Agent (persona) → Skill (capacidade). → [ADR-0004](../docs/adr/0004-tres-camadas-ia-agente-skill.md)
- **SIGMA nunca executa diretamente**, apenas orquestra via Agent → Skill. → [ADR-0005](../docs/adr/0005-sigma-nunca-executa-diretamente.md)
- **Toda integração externa é uma Skill**, com contrato único (Config/Permissões/Entrada/Saída/Eventos/Logs/Testes/Docs). → [ADR-0006](../docs/adr/0006-integracao-externa-e-sempre-uma-skill.md)
- **Proibido acesso direto a banco de outro sistema** — só via API. → [ADR-0007](../docs/adr/0007-comunicacao-somente-via-api.md)
- **Arquitetura orientada a eventos**, Redis como backbone. → [ADR-0008](../docs/adr/0008-arquitetura-orientada-a-eventos.md)
- **Stack de referência**: Laravel 12/PHP 8.4 (backend), React/TS/Vite PWA (web), React Native/Expo (mobile). → [ADR-0009](../docs/adr/0009-stack-tecnologica-de-referencia.md)
- **Desenvolvimento por épico único, com aprovação obrigatória antes de código.** → [ADR-0010](../docs/adr/0010-processo-por-epicos-com-aprovacao.md)
- Sprint 0 (Foundation) entregue: README, VISION, ARCHITECTURE, ROADMAP, ADR-0001–0010, CONTRIBUTING, CODE_OF_CONDUCT, LICENSE, estrutura de diretórios. Commitado localmente, push não autorizado ainda.
- **Revisão (Sprint 0.1)**, a pedido do responsável do projeto, atuando como revisor externo (papel de CTO):
  - Repositório renomeado de `Sigma-IO` para `project-sigma` — o nome anterior sugeria escopo de I/O, menor que a ambição real da plataforma. O repositório GitHub remoto (`FelipeGat/Sigma-IO`) segue com o nome antigo até ser renomeado manualmente ou até o primeiro push, quando essa ação será revisitada.
  - Arquitetura reestruturada de um "Mission Engine" único para **9 Engines especializados**: Kernel, Intent Engine, Planner Engine, Mission Engine, Memory Engine, Agent Engine, Skill Engine, Execution Engine, Audit Engine. → [ADR-0011](../docs/adr/0011-arquitetura-em-camadas-de-engines.md)
  - **O Planner Engine decide o plano — nunca a IA/Agent.** Refinamento explícito de [ADR-0005](../docs/adr/0005-sigma-nunca-executa-diretamente.md). → [ADR-0012](../docs/adr/0012-planner-decide-nunca-a-ia.md)
  - **Intent Engine** isolado como porta de entrada de linguagem natural, antes do Planner. → [ADR-0013](../docs/adr/0013-intent-engine-como-porta-de-entrada.md)
  - **SIGMA é um Sistema Operacional, não uma IA** — nomenclatura dos módulos centrais padronizada com sufixo "Engine" para reforçar isso na documentação e, mais adiante, no código. → [ADR-0014](../docs/adr/0014-sigma-e-um-sistema-operacional-nao-uma-ia.md)
  - Roadmap reestruturado de lista de épicos por funcionalidade para **sequência por camada de Engine** (Foundation → Kernel → Intent → Planner → Mission → Memory → Agent → Skill → Execution → Audit → Interfaces → Automation → Analytics), mantendo o princípio de um épico por vez. → [ADR-0015](../docs/adr/0015-roadmap-por-camadas-nao-por-feature.md)
  - Documentação de filosofia (`MANIFESTO.md`), produto (`PRODUCT.md`), horizonte de longo prazo (`VISION_2030.md`) e glossário de domínio (`DOMAIN.md`) adicionada.
  - Pastas `agents/`, `skills/`, `knowledge/`, `playbooks/`, `memory/` criadas na raiz do repositório — documentação/conhecimento, sem código.
  - Push ao GitHub segue **não autorizado** até esta revisão ser aprovada.
