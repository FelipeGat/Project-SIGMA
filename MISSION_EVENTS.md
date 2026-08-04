# Mission Events

Todo evento que o Mission Engine publica, o porquê de cada um existir, e como ele se encaixa no ciclo de vida descrito em [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md). Entrega obrigatória antes de qualquer código da Release 5, mesmo padrão de [DOMAIN_EVENTS.md](DOMAIN_EVENTS.md) para Identity/Memory — a diferença é que Mission já tinha uma parte do seu catálogo publicada há mais tempo (o catálogo Technical canônico em [EVENT_MODEL.md](EVENT_MODEL.md), escrito na Foundation), então este documento também reconcilia o que já existia com o que é novo, em vez de partir do zero.

**Diferença deliberada em relação a Identity/Memory**: os eventos de Identity/Memory são todos camada **Semantic** (fatos sobre o próprio agregado), então vivem em [DOMAIN_EVENTS.md](DOMAIN_EVENTS.md), documento explicitamente escopado a essa camada. Os eventos de Mission são camada **Technical** (orquestração entre Engines, ver [EVENT_MODEL.md — Três camadas de evento](EVENT_MODEL.md#três-camadas-de-evento)) — por isso o payload completo vive aqui, não em `DOMAIN_EVENTS.md`, e a lista de nomes canônicos vive em [EVENT_MODEL.md](EVENT_MODEL.md), não duplicada. [EVENT_CATALOG.md#mission-engine](EVENT_CATALOG.md#mission-engine) continua sendo a tabela mestra (consumidor/versão/contrato), mesmo padrão de todo Engine anterior.

## O que já existia (catálogo Technical de EVENT_MODEL.md, Foundation)

Três dos doze eventos originais já eram, de fato, do Mission Engine — só nunca tinham sido implementados:

| Evento | Quando |
|---|---|
| `SubtasksCreated` | Historicamente cobria "as Subtasks de uma Mission foram criadas" — na Release 5, corresponde ao momento em que o Mission Engine aceita um `Plan` e começa a materializar Subtasks candidatas em Subtasks reais (ver [MISSION_LIFECYCLE.md — Fluxo 2](MISSION_LIFECYCLE.md#fluxo-2--execução-retry-e-novos-gates)) |
| `MissionFinished` | A Mission atingiu `Completed` — validação final passou |
| `MissionCancelled` | A Mission foi cancelada manualmente, de qualquer estado ativo |

Os demais nove eventos do catálogo original (`MissionRequested`, `IntentDetected`, `IntentRejected`, `MissionPlanned`, `SubtaskAssigned`, `SkillRequested`, `ExecutionStarted`, `ExecutionValidated`, `ExecutionFailed`) **não são publicados pelo Mission Engine** — pertencem a Kernel/Intent Engine/Planner Engine (antes de uma Mission existir) ou a Agent/Skill/Execution Engine (depois, orquestrando uma Subtask específica). Mission Engine os **consome** (quando existirem — Releases 6-10), nunca os publica.

## O que é novo nesta Release — os dez eventos que faltavam

Formalizam exatamente o que [MISSION_MANIFESTO.md](MISSION_MANIFESTO.md#o-que-este-manifesto-acrescenta--a-parte-nova-pedida-explicitamente) pedia: aprovação, retry e compensação como parte de primeira classe do ciclo de vida, não um efeito colateral de log.

### Criação e aprovação (Fluxo 1)

- **`MissionCreated`** — a Mission passou a existir, a partir de um `Plan` aceito.
- **`MissionApprovalRequested`** — a Mission entrou em `PendingApproval`; um `ApprovalGate` novo aguarda decisão humana. **Nunca confundir com `IntentRejected`** (Intent Engine, antes de a Mission existir) — este é um gate sobre uma Mission já criada.
- **`MissionApproved`** — o `ApprovalGate` foi decidido favoravelmente; a Mission volta a `InProgress`.
- **`MissionRejected`** — o `ApprovalGate` foi decidido contra; a Mission vai para `Cancelled` sem nunca ter executado nenhuma Subtask (se o gate era o primeiro) ou sem executar as Subtasks restantes (se era um gate no meio da execução).
- **`MissionStarted`** — a Mission passou a `InProgress`, seja direto de `Created` (sem gate necessário) ou vindo de `PendingApproval` (após `MissionApproved`).

### Execução e retry (Fluxo 2)

- **`SubtaskRetried`** — uma Subtask falhou de forma transiente e uma nova tentativa foi registrada (`RetryAttempt`). A Mission continua `InProgress` — isto nunca muda o `MissionStatus`, só o histórico da Subtask.

### Falha sem compensação (Fluxo 2)

- **`MissionFailed`** — uma Subtask falhou definitivamente **sem** ter produzido efeito colateral (a distinção que decide entre este caminho e `Compensating` — ver [MISSION_LIFECYCLE.md — Fluxo 2, etapa 4](MISSION_LIFECYCLE.md#fluxo-2--execução-retry-e-novos-gates)). **Achado real durante a Implementation da Release 5B**: a primeira versão de [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md) descrevia esse caminho em prosa ("a Mission pode ir direto a `Failed` também") mas não catalogava um evento próprio para ele — só o caminho com compensação (`MissionCompensationFinished`) tinha um. Corrigido antes de escrever a classe de evento correspondente, mesma disciplina já aplicada ao achado de `MemoryReactivated` na Release 4A.

### Compensação (Fluxo 3)

- **`MissionCompensationStarted`** — uma Subtask falhou definitivamente com efeito colateral já produzido; a Mission entrou em `Compensating`.
- **`SubtaskCompensated`** — o registro de uma `Compensation` concluída (com sucesso ou não) para uma Subtask específica — o payload distingue `Compensated`/`CompensationFailed` (ver [MISSION_LIFECYCLE.md — Fluxo 3](MISSION_LIFECYCLE.md#fluxo-3--compensação)).
- **`MissionCompensationFinished`** — todas as compensações pendentes desta Mission foram resolvidas (com ou sem sucesso); a Mission vai para `Failed`.

## Payload mínimo de cada evento novo

| Evento | Nome no Event Bus | Payload mínimo |
|---|---|---|
| `MissionCreated` | `mission.created` | `missionId`, `correlationId`, `tenantId`, `workspaceId` (opcional), `intentId` (opcional) |
| `SubtasksCreated` | `subtasks.created` | `missionId`, `subtaskIds` |
| `MissionApprovalRequested` | `mission.approval_requested` | `missionId`, `approvalGateId`, `reason` |
| `MissionApproved` | `mission.approved` | `missionId`, `approvalGateId`, `decidedBy` |
| `MissionRejected` | `mission.rejected` | `missionId`, `approvalGateId`, `decidedBy` |
| `MissionStarted` | `mission.started` | `missionId` |
| `SubtaskRetried` | `subtask.retried` | `missionId`, `subtaskId`, `attemptNumber`, `reason` |
| `MissionFailed` | `mission.failed` | `missionId`, `subtaskId` |
| `MissionCompensationStarted` | `mission.compensation_started` | `missionId`, `subtaskId` |
| `SubtaskCompensated` | `subtask.compensated` | `missionId`, `subtaskId`, `result` (`Compensated`\|`CompensationFailed`) |
| `MissionCompensationFinished` | `mission.compensation_finished` | `missionId` |
| `MissionFinished` | `mission.finished` | `missionId` |
| `MissionCancelled` | `mission.cancelled` | `missionId`, `reason` |

## Regra de payload

Mesma regra já fixada em [EVENT_MODEL.md — Regras](EVENT_MODEL.md#regras): todo evento carrega, no mínimo, `missionId`, `correlationId`, `timestamp`, Engine publicador.

## O que este documento não decide

- O schema de serialização exato de cada payload — nasce durante a Implementation da Release 5A, junto do código que publica cada evento.
- Se algum destes eventos, com o tempo, é promovido a Business (ver [EVENT_MODEL.md](EVENT_MODEL.md)) — decisão do Automation Engine (Release 14)/Analytics (Release 15).

## Onde vive

Publicado por `packages/mission-engine/src/Domain/` sobre `IEventBus` ([packages/kernel](packages/kernel/)) — nunca conhecendo quem consome (mesmo princípio de [ADR-0062](docs/adr/0062-identity-nunca-conhece-outro-engine.md), aplicado a todo Engine desde então).
