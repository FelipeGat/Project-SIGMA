# ADR-0018: Tudo é Evento — o fluxo de Mission é modelado como sequência de eventos nomeados

- **Status**: Aceito — refina [ADR-0008](0008-arquitetura-orientada-a-eventos.md)
- **Data**: 2026-08-04

## Contexto

ADR-0008 já estabelece Event-Driven como arquitetura de comunicação entre contextos. Faltava tornar isso explícito como *filosofia central* do fluxo principal do sistema (a execução de uma Mission), com uma sequência canônica de eventos nomeados — sem isso, cada implementação de Engine poderia inventar sua própria forma de sinalizar progresso, e o sistema perderia a garantia de que "tudo que acontece é observável como evento".

## Decisão

O fluxo completo de uma Mission é modelado como uma sequência canônica e nomeada de eventos — `MissionRequested` → `IntentDetected` → `MissionPlanned` → `SubtaskAssigned` → `SkillRequested` → `ExecutionStarted` → `ExecutionValidated`/`ExecutionFailed` → `MissionFinished` — documentada e mantida como fonte única da verdade em [EVENT_MODEL.md](../../EVENT_MODEL.md). Nenhum Engine chama outro por invocação direta de função para sinalizar uma transição do fluxo principal; toda transição é um evento publicado.

## Consequências

- Qualquer novo consumidor (um dashboard, uma Automation, um Engine futuro) pode se conectar ao fluxo de uma Mission assinando eventos já existentes, sem exigir mudança em quem os publica.
- Depurar uma Mission travada se torna uma pergunta sobre "em qual evento ela parou", não uma investigação de pilha de chamadas — desde que Telemetry (ver [ADR-0019](0019-observabilidade-desde-o-dia-zero.md)) esteja correlacionando esses eventos por `trace_id`.
- Exige disciplina: é tentador, sob pressão de prazo, fazer um Engine chamar outro diretamente "só desta vez" — isso quebra a garantia central desta ADR e deve ser tratado como defeito de arquitetura, não atalho aceitável.
- [EVENT_MODEL.md](../../EVENT_MODEL.md) precisa ser atualizado sempre que um novo evento entra no fluxo canônico — divergência entre o catálogo documentado e o que o código realmente publica é considerada uma falha de processo.
