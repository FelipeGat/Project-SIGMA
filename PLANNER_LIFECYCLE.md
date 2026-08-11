# Planner Lifecycle

Como uma decisão de planejamento acontece, do recebimento de uma Intent até a publicação de um Plan — ou da impossibilidade de produzi-lo. Complementa [PLANNER_MODEL.md](PLANNER_MODEL.md) (o que existe) descrevendo **quando** cada coisa acontece.

## Não há máquina de estados

Este é o documento mais curto de ciclo de vida do projeto, e isso é consequência direta do desenho: **o Planner é sem estado**. Não existe `PlannerStatus`, não existe transição, não existe histórico.

Onde [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md) descreve quatro fluxos sobre um aggregate que vive e transita, aqui há um único fluxo que começa e termina numa operação — com dois desfechos possíveis.

Um `Plan` não "fica pronto" nem "fica pendente": ou ele é produzido na mesma operação, ou a operação falha. Não há estado intermediário observável.

## O fluxo único

```
Intent recebida
   │
   ├─ 1. Resolver PlanTemplate pelo `kind`
   │      └─ não existe → PlanningFailed (UnknownIntentKind)  ─┐
   │                                                           │
   ├─ 2. Validar `requiredParameters` contra `Intent.parameters`
   │      └─ falta algum → PlanningFailed (MissingRequiredParameter) ─┤
   │                                                           │
   ├─ 3. Traduzir cada PlanStep em SubtaskCandidate            │
   │      └─ zero candidatas → PlanningFailed (EmptyPlan)      ─┤
   │                                                           │
   └─ 4. Montar Plan (source: Planner) e publicar              │
          MissionPlanned                                       │
                                                               ▼
                                                    (fim, em ambos os casos)
```

Os três pontos de falha são verificados **em ordem**, e o primeiro que dispara encerra a operação. Isso importa: uma Intent de `kind` desconhecido com parâmetros faltando reporta `UnknownIntentKind`, nunca `MissingRequiredParameter` — reportar a falha mais externa primeiro é o que dá uma mensagem acionável a quem for diagnosticar.

## Passo 1 — Resolver o PlanTemplate

O `IntentKind` é um enum fechado, com exatamente um `PlanTemplate` por valor. Não há ambiguidade a resolver, nem precedência entre templates, nem template "genérico" de fallback.

**A ausência deliberada de fallback é uma decisão de desenho.** Um Planner que produz um plano genérico para um pedido que não entende é um Planner que executa a coisa errada com confiança — exatamente o risco que [PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md#o-planner-é-onde-o-sigma-decide) identifica como o pior modo de falha deste Engine. Falhar visivelmente é melhor.

## Passo 2 — Validar parâmetros

`PlanTemplate.requiredParameters` lista o que aquele Playbook chama de "Contexto necessário" e que **já precisa vir na Intent**. Nesta Release o Planner não busca contexto em lugar nenhum: não consulta Memory, não consulta Knowledge, não chama Skill (nem poderia — Skill Engine é a Release 8).

Consequência aceita: um Playbook cujo contexto só existe fora da Intent não pode virar `PlanTemplate` nesta Release. É a restrição que mantém o Planner uma função pura da Intent — e a primeira que provavelmente cairá quando houver necessidade real.

## Passo 3 — Traduzir passos em candidatas

Cada `PlanStep` vira um `SubtaskCandidate`, na mesma ordem, preservando `description`, `candidateAgent`, `candidateCapability` e `requiredAutonomyLevel`.

A tradução é 1:1 e sem lógica condicional nesta Release. O `requiredAutonomyLevel` **não** é comparado contra o `autonomyCeiling` da Intent aqui — o Planner só marca o nível; quem compara é o Mission Engine, quando puxa cada Subtask (`Mission::advanceToNextSubtask()`, Release 5B). Duplicar essa comparação no Planner criaria duas fontes de verdade sobre quando um `ApprovalGate` nasce.

## Passo 4 — Publicar

`MissionPlanned` é publicado com o `Plan` completo no payload. O Planner não cria Mission, não chama o Mission Engine, não espera resposta ([ADR-0089](docs/adr/0089-mission-nasce-do-plan-nao-da-intent.md)).

**O Planner nunca sabe se a Mission chegou a existir.** Se ninguém consumir `mission.planned`, o Plan se perde silenciosamente — e o Planner, sem estado, não tem como detectar isso. Esta é a consequência mais incômoda do desenho sem estado, e está registrada como pendência, não como característica desejável. É agravada pela limitação já conhecida do Redis pub/sub (sem replay, confirmada com evidência na Release 4.5).

## Desfecho de falha

`PlanningFailed` é publicado com `intentId`, `reason` e `detail`. Nenhum Plan é publicado; a operação termina.

Não há retry, não há fila de Intents não planejáveis, não há reprocessamento. Quem publicou a Intent decide o que fazer — e nesta Release, quem publica é uma chamada manual.

## Relação com o Mission Engine

O Mission Engine reage a `mission.planned` criando a Mission a partir do `Plan` recebido. Do lado do Planner isso é invisível: a fronteira é o payload do evento, e o [contrato](contracts/Planner.contract.yaml) é o que garante que as duas formas de `Plan` não divirjam.

`Plan.source` chega como `Planner` — o valor que existe desde a Release 5B no enum `PlanSource` e que, até agora, nunca havia sido produzido por ninguém. `Manual` continua válido para o caminho de `POST /missions` aberto na Release 5.5.

## O que este documento não decide

- Quem publica a Intent que o Planner consome — nesta Release, chamada manual; na Release 7, o Intent Engine.
- Como o Planner passa a ser um processo de verdade (worker consumindo `intent.detected`, ou caso de uso invocado por HTTP) — decisão da Proposal de Implementation.
- O que fazer com um `PlanningFailed` — nenhum Engine o consome ainda.
