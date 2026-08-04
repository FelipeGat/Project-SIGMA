# ADR-0012: O Planner Engine decide o plano — nunca a IA/Agent

- **Status**: Aceito — refina [ADR-0005](0005-sigma-nunca-executa-diretamente.md)
- **Data**: 2026-08-04

## Contexto

ADR-0005 já estabelece que SIGMA nunca executa diretamente — delega a um Agent, que age via Skill. Mas isso, sozinho, não responde a uma pergunta mais específica e mais crítica: **quem decide o que vai ser feito** dentro de uma Mission? Deixar essa decisão a cargo do próprio Agent/IA (ex: o Agent recebe a Mission inteira e decide livremente os passos) tornaria o comportamento do sistema tão previsível quanto o modelo de IA por trás do Agent naquele momento — e, por definição, menos auditável e menos substituível.

## Decisão

O **Planner Engine** — parte do núcleo do SIGMA, não uma IA — é o único responsável por decidir o Plan de uma Mission: quais Subtasks existem, em que ordem, e quais Agentes/Skills são candidatos para cada uma. Um Agent recebe sempre uma Subtask já definida pelo Planner Engine; nunca a Mission inteira para decidir sozinho o que fazer com ela.

## Consequências

- O comportamento do SIGMA diante de um mesmo tipo de pedido é consistente e auditável, independente de qual IA está por trás do Agent que executa cada Subtask.
- Trocar a IA de um Agent nunca muda *o que* o sistema decide fazer — só *como* uma Subtask específica é executada.
- O Planner Engine se torna um componente crítico e concentrado — precisa ser bem testado e, quando usar IA para apoiar a decisão de planejamento, o resultado permanece sujeito a validação e a regras do próprio Planner, não repassado cegamente.
- [Playbooks](../../playbooks/) existem justamente para dar ao Planner Engine, desde já em forma documental, o conhecimento de como planejar tipos recorrentes de Mission — ver [/playbooks/README.md](../../playbooks/README.md).
