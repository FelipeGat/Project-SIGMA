# Planner Events

Os eventos do Planner Engine, catalogados na Release 6A — antes de qualquer código, seguindo [ADR-0082](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) e a mesma disciplina de [MISSION_EVENTS.md](MISSION_EVENTS.md).

## São dois, e um deles já existia no papel

| Evento | Bus | Camada | Situação |
|---|---|---|---|
| `MissionPlanned` | `mission.planned` | Technical | Catalogado em [EVENT_MODEL.md](EVENT_MODEL.md) desde a Release 1, **sem payload especificado** — esta Release o especifica |
| `PlanningFailed` | `planning.failed` | Technical | **Novo** — não existia no catálogo |

Camada **Technical**, não Semantic: ambos descrevem a orquestração entre Engines, não um fato de negócio que a empresa reconheceria por si só ([EVENT_MODEL.md](EVENT_MODEL.md) — as três camadas). O fato de negócio correspondente é `MissionCreated`, publicado pelo Mission Engine depois de consumir `mission.planned`.

## `MissionPlanned` — `mission.planned`

Publicado quando um `Plan` foi produzido com sucesso. É o único caminho pelo qual uma Mission passa a existir a partir de um planejamento real ([ADR-0089](docs/adr/0089-mission-nasce-do-plan-nao-da-intent.md)).

```json
{
  "intentId": "uuid",
  "correlationId": "uuid",
  "tenantId": "uuid",
  "workspaceId": "uuid|null",
  "objective": "string",
  "autonomyCeiling": 0,
  "actor": { "type": "user|system|agent", "id": "string" },
  "plan": {
    "source": "planner",
    "subtaskCandidates": [
      {
        "description": "string",
        "candidateAgent": "string|null",
        "candidateCapability": "string|null",
        "requiredAutonomyLevel": 0
      }
    ]
  }
}
```

**O payload carrega tudo que o Mission Engine precisa para criar a Mission sem consultar ninguém** — `correlationId`, `tenantId`, `workspaceId`, `objective`, `actor` e `autonomyCeiling` vêm junto, não apenas o `plan`. Isso é deliberado: um consumidor que precisasse buscar o resto em outro lugar acoplaria Mission a Planner por chamada, exatamente o que [ADR-0092](docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md) evita.

`correlationId` é o campo que faz esse payload ser suficiente na prática: `Mission::create()` o exige desde a Release 5B, e sem ele o consumidor teria que gerar um novo — quebrando a rastreabilidade fim-a-fim justamente na fronteira entre decidir e executar. Ele atravessa o Planner sem ser alterado, vindo do Envelope de quem originou o pedido.

`plan.subtaskCandidates` **nunca é vazio** — um plano vazio é `PlanningFailed`, não `MissionPlanned`.

`plan.source` é sempre `"planner"` aqui. O valor `"manual"` existe no mesmo enum desde a Release 5B e continua válido para o caminho `POST /missions` aberto na Release 5.5.

| Publica | Consome |
|---|---|
| Planner Engine | Mission Engine (cria a Mission), Audit Engine (Release 11) |

## `PlanningFailed` — `planning.failed`

Publicado quando a Intent foi entendida mas nenhum Plan pôde ser produzido. **Evento novo, catalogado antes do código** — mesma disciplina de `MissionFailed` na Release 5B e `MemoryReactivated` na 4A.

```json
{
  "intentId": "uuid",
  "correlationId": "uuid",
  "tenantId": "uuid",
  "workspaceId": "uuid|null",
  "reason": "unknown_intent_kind|missing_required_parameter|empty_plan",
  "detail": "string"
}
```

### Por que não bastava `IntentRejected`

`IntentRejected` (`intent.rejected`) pertence ao **Intent Engine** e significa *"não entendi o pedido"*. `PlanningFailed` significa *"entendi o pedido e não sei como executá-lo"*.

São diagnósticos opostos para quem for investigar: o primeiro aponta para a interpretação da linguagem, o segundo para uma lacuna de `PlanTemplate`/Playbook — ou seja, para conhecimento de negócio que ninguém documentou ainda. Reutilizar `IntentRejected` para os dois apagaria essa distinção justamente no momento em que ela é mais útil.

| Publica | Consome |
|---|---|
| Planner Engine | Ninguém ainda — Audit Engine (Release 11) é o consumidor natural |

**Nenhum Engine consome `planning.failed` nesta Release.** Registrado como pendência, no mesmo padrão já aceito para vários eventos do Memory Engine na 4B: o evento existe e é publicado corretamente; o consumidor chega quando houver um.

## Regra de payload

Mesma dos demais Engines: identificadores como `string` (UUID), enums pelo seu valor serializado (`snake_case`), nunca objetos de domínio serializados inteiros. Um consumidor precisa entender o payload sem importar código do Planner.

## O que este documento não decide

- **Metadata padrão de evento** — [ADR-0076](docs/adr/0076-metadata-padrao-em-eventos-de-dominio.md) aprovou a direção (`id`/`timestamp`/`correlationId`/`causationId`/`actor`/`workspace` como envelope de metadata separado do payload) e nenhum Engine a implementou. O Planner **não** abre esse precedente sozinho: `correlationId` e `actor` viajam como campos comuns do payload, não como metadata estruturada. Quando a ADR-0076 for implementada, estes dois eventos migram junto com os demais.
- **Versionamento** — ambos nascem `v1`; a política de evolução é a do [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md).
- **Entrega garantida** — Redis pub/sub não tem replay (confirmado com evidência real na Release 4.5). Se ninguém estiver ouvindo `mission.planned`, o Plan se perde e o Planner, sem estado, não detecta. Ver [PLANNER_LIFECYCLE.md](PLANNER_LIFECYCLE.md#passo-4--publicar).

## Onde vive

`packages/planner-engine/src/Domain/Event/`. Catalogados também em [EVENT_CATALOG.md](EVENT_CATALOG.md) e [contracts/Planner.contract.yaml](contracts/Planner.contract.yaml).
