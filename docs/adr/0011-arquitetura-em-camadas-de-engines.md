# ADR-0011: Arquitetura em camadas de Engines especializados

- **Status**: Aceito — refina [ADR-0003](0003-mission-como-entidade-central.md)
- **Data**: 2026-08-04

## Contexto

O desenho inicial (Sprint 0) concentrava interpretação, planejamento e execução da Mission num único "Mission Engine". Em revisão, ficou claro que isso mistura responsabilidades diferentes sob um nome só: entender o pedido, decidir como fazê-lo, acompanhar o progresso, e validar o resultado são preocupações distintas, com motivos de mudança distintos (SRP).

## Decisão

O núcleo de orquestração do SIGMA é dividido em nove Engines especializados, cada um com uma responsabilidade única:

1. **Kernel** — ciclo de vida da plataforma (bootstrap, configuração, contexto de execução, health).
2. **Intent Engine** — interpreta linguagem natural/eventos em uma Intent estruturada.
3. **Planner Engine** — monta o Plan de execução a partir de uma Intent.
4. **Mission Engine** — gerencia a Mission e seu progresso a partir de um Plan.
5. **Memory Engine** — organiza Knowledge e Memory.
6. **Agent Engine** — decide qual Agent executa cada Subtask.
7. **Skill Engine** — conversa com sistemas externos via Skills.
8. **Execution Engine** — acompanha e valida a execução em andamento.
9. **Audit Engine** — registra Events e Logs para rastreabilidade.

Interfaces (Web/Mobile), Automation e Analytics são camadas que consomem estes Engines, não Engines centrais do domínio. Ver o diagrama atualizado em [docs/architecture/ARCHITECTURE.md](../architecture/ARCHITECTURE.md).

## Consequências

- Cada Engine pode evoluir, ser testado e (no horizonte de [VISION_2030.md](../../VISION_2030.md)) ser extraído para repositório próprio de forma independente.
- O ciclo de vida da Mission (antes um bloco só) passa a ser explicitamente rastreável por Engine: é possível saber se uma Mission está travada na interpretação, no planejamento, na execução ou na validação.
- Aumenta o número de contratos (interfaces) entre Engines em relação ao desenho anterior — aceito conscientemente como o custo de manter cada Engine substituível isoladamente.
- Exige que o Épico E1 original ("Mission Engine — núcleo", Sprint 0) seja reescopado; o roadmap é reestruturado por Engine em [ADR-0015](0015-roadmap-por-camadas-nao-por-feature.md).
