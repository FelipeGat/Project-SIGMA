# Mission Lifecycle

Como uma Mission nasce de um `Plan`, percorre execução, aprovação, retry e compensação até um dos três desfechos finais — o equivalente, para Mission, do que [IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md) é para Identity e [MEMORY_LIFECYCLE.md](MEMORY_LIFECYCLE.md) é para Memory. Pré-requisito para a implementação da Release 5, ao lado de [MISSION_MODEL.md](MISSION_MODEL.md).

Este documento **reconcilia três descrições pré-existentes do mesmo fluxo**, escritas em momentos diferentes do projeto com vocabulários diferentes — nenhuma delas errada, todas parciais:

- [ADR-0003](docs/adr/0003-mission-como-entidade-central.md): `Interpretar → Planejar → Criar Plano → Criar Subtarefas → Escolher Skills → Executar → Validar → Registrar → Concluir` (prosa, 9 passos).
- [ARCHITECTURE.md §6](docs/architecture/ARCHITECTURE.md): um `stateDiagram-v2` com 11 estados, incluindo estágios que hoje sabemos pertencer a Intent/Planner Engine, não a Mission (ver [MISSION_MODEL.md — Onde o domínio de Mission começa](MISSION_MODEL.md#onde-o-domínio-de-mission-começa--e-onde-não-começa)).
- [EVENT_MODEL.md](EVENT_MODEL.md): 12 eventos Technical canônicos, a fonte de nomes já estável.

Nenhum dos três é reescrito — os três continuam existindo e corretos para o que descrevem. Este documento é a versão que separa explicitamente o que acontece **antes** de uma `Mission` existir (Intent/Planner, fora do escopo do Mission Engine) do que acontece **depois** (o próprio ciclo de vida de uma `Mission`, meu escopo), e acrescenta o que nenhum dos três ainda descrevia: aprovação, retry, compensação.

## Fluxo 0 — Antes de uma Mission existir (fora do escopo do Mission Engine)

```
Intent recebida (Kernel: MissionRequested)
      ↓
Intent Engine interpreta (IntentDetected | IntentRejected)
      ↓ (se detectada)
Planner Engine decompõe em um ou mais Plans candidatos (MissionPlanned)
      ↓
Mission Engine recebe cada Plan e cria uma Mission — Fluxo 1 começa aqui
```

Citado apenas para deixar a fronteira explícita — `IntentDetected`/`IntentRejected`/`MissionPlanned` são publicados por Intent Engine (Release 7)/Planner Engine (Release 6), nunca pelo Mission Engine. Na Release 5, sem essas Engines existirem ainda, um `Plan` é fornecido manualmente ([ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md)) — o Fluxo 1 abaixo começa igual, seja o `Plan` real ou mockado.

## Fluxo 1 — Criação e aprovação inicial

```
Plan recebido (de um teste, ou futuramente do Planner Engine)
      ↓
Mission criada — status: Created (evento: MissionCreated)
      ↓
autonomyCeiling suficiente para a primeira Subtask?
      ↓ não                              ↓ sim
PendingApproval                     InProgress
(MissionApprovalRequested)          (MissionStarted)
      ↓ decisão humana
      ↓ aprovado (MissionApproved) → InProgress
      ↓ rejeitado (MissionRejected) → Cancelled
```

### 1. `Plan` recebido, `Mission` criada

O Mission Engine aceita um `Plan` e cria a `Mission` correspondente — status inicial `Created`, `subtasks` ainda vazio (as Subtasks "em execução" nascem uma a uma, não todas de uma vez, ver Fluxo 2). Publica `MissionCreated`.

### 2. Avaliação do primeiro gate de autonomia

Se `autonomyCeiling` (resolvido do User/Role que originou a Mission) não é suficiente para a primeira Subtask do Plan prosseguir sem confirmação, a Mission entra em `PendingApproval` com um `ApprovalGate` novo, publicando `MissionApprovalRequested`. Caso contrário, vai direto para `InProgress`, publicando `MissionStarted`.

### 3. Decisão humana (quando pendente)

Uma decisão (`Approved`/`Rejected`) resolve o `ApprovalGate`. Aprovado → `InProgress` (`MissionApproved`). Rejeitado → `Cancelled` (`MissionRejected`) — a Mission nunca chega a executar nenhuma Subtask.

## Fluxo 2 — Execução, retry e novos gates

```
InProgress
      ↓
Próxima Subtask candidata do Plan → Subtask criada (status: Pending)
      ↓
autonomyCeiling suficiente para esta Subtask?
      ↓ não → PendingApproval (mesma Mission, novo ApprovalGate) → decisão → volta para InProgress ou Cancelled
      ↓ sim
Subtask: Pending → Assigned → Executing
      ↓
Execução falhou (transiente)?
      ↓ sim → RetryAttempt registrado (SubtaskRetried) → volta para Executing, até esgotar a política
      ↓ não/esgotada
Subtask: Validated (sucesso) ou Failed (falha definitiva)
      ↓ (Failed, com efeito colateral já produzido)
Mission → Compensating — Fluxo 3
      ↓ (todas as Subtasks Validated)
Mission → Validating — Fluxo 4
```

### 1. Uma Subtask por vez, a partir do Plan

O Mission Engine não cria todas as `Subtask` de uma vez — cada Subtask candidata do `Plan` só vira uma `Subtask` "em execução" quando é a vez dela. Isso mantém o `ApprovalGate` significativo: aprovar "a próxima Subtask" é uma decisão menor e mais rápida do que aprovar "o Plan inteiro de uma vez".

### 2. Gate de autonomia por Subtask

Cada Subtask nova pode, por si só, exigir um `ApprovalGate` novo (mesmo mecanismo do Fluxo 1) — o `autonomyCeiling` da Mission nunca é ultrapassado, mesmo que a Capability específica exigisse menos.

### 3. Retry é histórico, não mudança de estado da Mission

Uma falha transiente de execução gera um `RetryAttempt`, publica `SubtaskRetried`, e a Subtask volta a `Executing` — a Mission continua `InProgress` o tempo todo. Só quando a política de retry se esgota (número exato de tentativas, decisão de Implementation) é que a falha se torna definitiva.

### 4. Falha definitiva → Compensação (Fluxo 3) ou simplesmente `Failed`

Se a Subtask falhou definitivamente **sem** ter produzido nenhum efeito colateral em sistema externo (ex: uma Capability de leitura), a Subtask vira `Failed` e a Mission pode ir direto a `Failed` também, sem precisar de compensação. Se a Subtask **já produziu efeito** (ex: uma Capability de escrita que só falhou na confirmação, mas o efeito já aconteceu), a Mission entra em `Compensating` (Fluxo 3) — a distinção exata de "produziu efeito ou não" é decisão de Implementation, informada por metadados já existentes na Capability (`riskLevel` do Envelope, ver [SIGMA_PROTOCOL.md §1](SIGMA_PROTOCOL.md#1-o-envelope)).

## Fluxo 3 — Compensação

```
Mission: Compensating (MissionCompensationStarted)
      ↓
Para a Subtask que falhou com efeito já produzido:
      ↓
Compensation registrada — Compensated ou CompensationFailed
      ↓ (Compensated)                    ↓ (CompensationFailed)
Mission → Failed                    Mission → Failed
(MissionCompensationFinished,       (MissionCompensationFinished,
 efeito desfeito/sinalizado)         efeito NÃO desfeito — sinalizado
                                      com prioridade máxima para revisão humana)
```

Uma Mission que passou por `Compensating` **sempre** termina em `Failed`, nunca em `Completed` — mesmo que a compensação em si tenha tido sucesso, o objetivo original da Mission não foi alcançado. O que muda entre `Compensated` e `CompensationFailed` é a gravidade do que fica pendente para um humano resolver, não o `MissionStatus` final.

## Fluxo 4 — Validação e conclusão

```
Todas as Subtasks Validated
      ↓
Mission: Validating
      ↓
Validação final passou?
      ↓ sim                    ↓ não (tratado como falha de Subtask)
Mission: Completed         Mission: Compensating (Fluxo 3) ou Failed
(MissionFinished)          (mesma lógica do Fluxo 2, etapa 4)
```

`Validating` é deliberadamente um estado curto — a checagem final de que o resultado agregado das Subtasks realmente cumpre o `objective` da Mission, não uma nova rodada de execução. "Registrada" (a etapa do `ARCHITECTURE.md §6` original) não é mais um estado que bloqueia nada — o Audit Engine consome `MissionFinished` reativamente, como qualquer outro consumidor de evento; não é um gate que a Mission espera.

## Cancelamento — de qualquer estado ativo

`Created`, `PendingApproval`, `InProgress`, `Compensating` e `Validating` podem, a qualquer momento, receber um cancelamento manual — publica `MissionCancelled`, `status: Cancelled`. Diferente de `Failed`, `Cancelled` nunca passa por `Compensating` automaticamente — se um cancelamento manual precisa desfazer efeito já produzido, isso é decisão de quem cancela, não uma reação automática do Mission Engine (decisão deliberada, evita o Mission Engine tomar uma ação de compensação sem ter sido, ele mesmo, quem decidiu que havia falha).

## Onde vive

Implementado por `packages/mission-engine` (Release 5), disponibilizado a todo Engine seguinte através do Kernel — mesmo padrão de disponibilização já usado por Identity (`Context`/`Identity`) e Memory (`MemoryRecord`/`DigitalTwin`).
