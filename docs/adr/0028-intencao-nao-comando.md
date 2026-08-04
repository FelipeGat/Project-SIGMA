# ADR-0028: SIGMA executa Intenções, não comandos — uma Intent pode decompor em múltiplas Missions

- **Status**: Aceito — refina [ADR-0003](0003-mission-como-entidade-central.md) e o modelo Intent→Mission de [DOMAIN.md](../../DOMAIN.md)
- **Data**: 2026-08-04

## Contexto

O modelo original tratava uma Intent como algo que gera exatamente um Plan, que gera exatamente uma Mission. Isso é suficiente para um pedido simples, mas não reflete como uma solicitação real costuma se parecer: "Sigma, participe da reunião da Sea Master, atualize o Gestor, ajuste o orçamento e avise o Victor" não são quatro comandos encadeados — é uma única intenção ("conduzir o pós-reunião comercial da Sea Master") que, ao ser compreendida, se decompõe em múltiplas ações relacionadas. Tratar isso como quatro Missions independentes, sem uma Intent comum as amarrando, perderia o contexto de que elas pertencem ao mesmo objetivo.

## Decisão

O SIGMA interpreta **Intenções**, não comandos. Uma Intent representa um objetivo (`"Conduzir o pós-reunião comercial da Sea Master"`), e o Planner Engine pode decompô-la em **uma ou mais Missions relacionadas**, cada uma com suas próprias Subtasks, todas rastreáveis à Intent de origem. A cardinalidade Intent → Mission deixa de ser 1:1 e passa a ser 1:N.

```
Intent ("conduzir o pós-reunião comercial da Sea Master")
├── Mission 1: Registrar participação na reunião
├── Mission 2: Atualizar Gestor.Alfa com o resultado
├── Mission 3: Ajustar orçamento
└── Mission 4: Notificar Victor
```

## Consequências

- O sistema passa a ser orientado a objetivos, não reativo a uma lista de comandos — a mesma frase do usuário pode gerar Missions diferentes dependendo do contexto (ex: se o orçamento já estava correto, a Mission 3 nem é criada), porque o Planner raciocina sobre o objetivo, não sobre uma lista literal de instruções.
- Rastreabilidade melhora: o Audit Engine consegue responder não só "o que a Mission X fez" mas "todas as Missions que nasceram da mesma Intent Y" — relevante para entender o resultado completo de um pedido composto.
- Exige que `DOMAIN.md` e o diagrama de relação entre camadas sejam atualizados para refletir a cardinalidade 1:N — feito nesta mesma revisão.
- Aumenta a complexidade do Planner Engine (Release 6): decompor uma Intent em múltiplas Missions coerentes é uma tarefa mais difícil do que gerar um único Plan — aceito conscientemente como o preço de o sistema ser realmente orientado a objetivos, não apenas um roteador de comandos com um nome mais sofisticado.
