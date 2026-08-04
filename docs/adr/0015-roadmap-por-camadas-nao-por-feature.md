# ADR-0015: Roadmap organizado por camada de Engine, não por feature

- **Status**: Aceito — substitui a organização de épicos do Sprint 0 em [ROADMAP.md](../../ROADMAP.md)
- **Data**: 2026-08-04

## Contexto

O roadmap do Sprint 0 organizava épicos por funcionalidade de produto (Mission Engine básico, Identidade, Skill Registry, Orquestração de Agentes, Knowledge/Memory, Painel Web, Mobile, Automação, Expansão de Skills). Com a arquitetura reestruturada em nove Engines especializados ([ADR-0011](0011-arquitetura-em-camadas-de-engines.md)), essa organização por feature deixa de refletir a ordem de dependência real entre os componentes — por exemplo, o Agent Engine não faz sentido sem o Planner Engine já existir para lhe entregar Subtasks.

## Decisão

O roadmap passa a seguir a ordem das camadas: **Foundation → Kernel → Intent Engine → Planner Engine → Mission Engine → Memory Engine → Agent Engine → Skill Engine → Execution Engine → Audit Engine → Interfaces (Web/Mobile) → Automation → Analytics**. Cada camada continua sendo entregue como um ou mais épicos únicos, com aprovação obrigatória antes de código — [ADR-0010](0010-processo-por-epicos-com-aprovacao.md) permanece em vigor, apenas a ordem/escopo dos épicos muda.

## Consequências

- A ordem de construção segue a ordem real de dependência entre Engines — reduz o risco de construir um Engine que só funciona quando outro, ainda não iniciado, existir.
- Um "fluxo ponta a ponta" simples (uma Mission de teste percorrendo Intent → Planner → Mission → Agent → Skill → Execution → Audit) só é possível depois que os primeiros Engines centrais existirem — a validação end-to-end acontece mais tarde nesta ordem do que aconteceria numa organização por feature, e isso é aceito conscientemente em troca de uma base mais sólida por camada.
- Interfaces, Automation e Analytics ficam deliberadamente depois dos Engines centrais — são consumidores dos Engines, não o contrário.
