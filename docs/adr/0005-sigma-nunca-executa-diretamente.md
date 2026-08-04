# ADR-0005: SIGMA nunca executa diretamente — atua somente como orquestrador

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Era preciso decidir se o núcleo do SIGMA (Mission Engine) teria lógica própria para, por exemplo, chamar a API do WhatsApp ou escrever num sistema externo, ou se essa responsabilidade seria sempre delegada.

## Decisão

O Mission Engine nunca executa uma ação no mundo diretamente. Ele decide *o quê* fazer (interpretação e planejamento da Missão) e delega o *como* a um Agente, que por sua vez age através de uma Skill. Ver [ADR-0004](0004-tres-camadas-ia-agente-skill.md).

## Consequências

- O núcleo de orquestração permanece pequeno, estável e livre de lógica específica de integração — o que muda quando uma API externa muda é a Skill, não o Mission Engine.
- Toda ação executada pelo sistema é atribuível a uma Skill específica, o que torna log e auditoria diretos: toda linha de log de execução tem uma Missão, um Agente e uma Skill de origem.
- Introduz latência e uma camada de indireção adicional em toda execução — aceito conscientemente em troca de auditabilidade e substituibilidade.
