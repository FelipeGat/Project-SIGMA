# ADR-0004: Separação em três camadas — IA (provedor), Agente (persona), Skill (capacidade)

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

SIGMA orquestra múltiplas inteligências artificiais, cada uma com uma especialidade (Claude para Engenharia, ChatGPT para Estratégia, Gemini para Design, Manus para Documentação), e múltiplas integrações externas (GitHub, WhatsApp, Gestor.Alfa...). Modelar "IA" e "capacidade de ação" como um conceito único acopla a lógica de negócio ao provedor de IA escolhido e mistura duas responsabilidades diferentes: quem pensa e o que pode ser feito no mundo.

## Decisão

O domínio de execução é dividido em três camadas independentes:

1. **IA** — o provedor/modelo bruto: credenciais, limites, custo, capacidades técnicas.
2. **Agente** — uma persona operacional com uma especialidade, que usa uma IA: "Agente de Engenharia" usa Claude, "Agente de Estratégia" usa ChatGPT.
3. **Skill** — uma capacidade concreta de ação no mundo (integração), invocada por um Agente para cumprir uma subtarefa de Missão.

Um Agente nunca acessa uma API externa diretamente; ele solicita a uma Skill autorizada.

## Consequências

- Trocar o provedor de IA por trás de um Agente (ex: migrar o Agente de Estratégia de ChatGPT para outro modelo) não exige tocar em Skill nem em Mission.
- Adicionar uma nova especialidade de Agente não exige nova infraestrutura de integração — reaproveita as Skills já existentes.
- Permissão, log e contrato de entrada/saída ficam centralizados na Skill, não duplicados em cada Agente que a usa.
- Exige uma camada de indireção a mais (AgentPort) na implementação — aceito conscientemente em troca de substituibilidade.
