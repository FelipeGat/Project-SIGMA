# ADR-0025: SIGMA Protocol é a Release 1 — antes do Kernel, antes de qualquer Engine

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O roadmap original (Sprint 0.1/0.2) colocava o Kernel como primeira camada de implementação, por ser pré-requisito técnico de bootstrap dos demais Engines. Em revisão, o Product Owner apontou uma lacuna anterior a isso: sabemos que Mission, Skill, Agent, Memory, Workspace existem como conceitos, mas não havia, antes desta decisão, um documento único definindo **como essas peças conversam entre si** — o formato de mensagem, o envelope de resposta, o contrato de cada relação (Intent↔Planner, Planner↔Memory, Agent↔Skill, etc.). Construir o Kernel e os Engines sem esse contrato definido cria o risco de cada Engine inventar seu próprio formato de comunicação, exigindo retrabalho de integração entre todos eles.

## Decisão

**Release 1 é a especificação do SIGMA Protocol** ([SIGMA_PROTOCOL.md](../../SIGMA_PROTOCOL.md)) — não código. Define o envelope de resposta padronizado, o conceito de Capability, a filosofia de execução por Intenção (não comando), e o modelo de Autonomia Progressiva, antes de qualquer Engine ser implementado. A ordem de releases seguinte é: Release 1 Protocol → Release 2 Kernel → Release 4 Memory Engine → Release 5 Mission Engine → Release 6 Planner Engine → Release 7 Intent Engine → Release 8 Skill Engine → Release 9 Agent Engine → (Execution, Audit, Interfaces, Automation, Analytics seguem depois, ordem inalterada). Ver [ROADMAP.md](../../ROADMAP.md).

## Nota de transparência sobre a ordem Planner → Intent

Esta ordem coloca o **Planner Engine (Release 6) antes do Intent Engine (Release 7)** — o que, à primeira vista, parece inverter a dependência já registrada em [ADR-0012](0012-planner-decide-nunca-a-ia.md) e [ADR-0013](0013-intent-engine-como-porta-de-entrada.md) ("o Planner recebe uma Intent já estruturada"; ver também a sequência canônica em [EVENT_MODEL.md](../../EVENT_MODEL.md), onde `IntentDetected` precede `MissionPlanned`). A leitura que torna essa ordem coerente — e que fica registrada aqui para confirmação, não assumida silenciosamente — é: com o SIGMA Protocol (Release 1) já definindo o formato de uma `Intent`, o Planner Engine pode ser construído e testado contra Intents estruturadas manualmente/mockadas (o mesmo padrão já usado no roadmap anterior para Mission Engine sem Agent real, ver antiga camada L4), e só depois o Intent Engine é construído para produzir essas Intents automaticamente a partir de linguagem natural. Se essa não for a intenção do Product Owner, a ordem entre Release 6 e Release 7 deve ser revista antes de a Release 6 começar.

## Consequências

- Todo Engine implementado a partir da Release 2 já nasce falando o mesmo protocolo — reduz o risco de retrabalho de integração entre Engines.
- O SIGMA Protocol se torna o documento de maior autoridade técnica do projeto, acima de [ARCHITECTURE.md](../../docs/architecture/ARCHITECTURE.md) em decisões de formato/contrato — ARCHITECTURE.md descreve a topologia (quem fala com quem); SIGMA_PROTOCOL.md descreve a língua (o que é dito).
- Adiciona uma release inteira antes de qualquer Engine existir — mais tempo até o primeiro código rodando de fato, aceito conscientemente pelo Product Owner como o custo de acertar o contrato antes de construir sobre ele.
- Mudanças no protocolo depois que Engines já o implementam são caras (exigem migração coordenada) — reforça a importância de revisar bem a Release 1 antes de aprovar a Release 2.
