# Memory Lifecycle

Como um fato observado vira `MemoryRecord`, como ele é promovido entre níveis, e como um `DigitalTwin` se mantém sincronizado — o equivalente, para Memory, do que [IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md) é para Identity. Pré-requisito para a implementação da Release 4, ao lado de [MEMORY_MODEL.md](MEMORY_MODEL.md).

## Fluxo 1 — Observação e promoção de Memory

```
Fato observado numa Mission
      ↓
MemoryRecord criado (Operational)
      ↓
Mesmo subjectKey, mesmo Workspace, Mission diferente?
      ↓ sim (repetição)
Promovido a Project (sourceMissionIds acumulado, promotedFrom preenchido)
      ↓
Mesmo subjectKey (ou generalizado), Workspace diferente?
      ↓ sim (generalização)
Promovido a Long Term
```

### 1. Fato observado numa Mission

Uma Mission em andamento produz uma observação — não é este documento que define o que conta como "observação digna de virar Memory" (isso é trabalho do Planner/Mission Engine, Releases 5/6, decidindo o que vale a pena registrar). O Memory Engine só recebe o fato já formado: `subjectKey` + `content` + `missionId` + `workspaceId` + `tenantId`.

### 2. `MemoryRecord` criado como `Operational`

Nível mais baixo, vida útil ligada à Mission de origem — ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md). Neste ponto, o fato é só uma observação isolada, sem nenhuma confirmação de que se repete.

### 3. Avaliação de repetição (`Operational → Project`)

Quando `EvaluatePromotion` roda (sob demanda nesta Release — ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-a-promoção-funciona)) e encontra o mesmo `subjectKey` associado a mais de uma `missionId` dentro do mesmo `workspaceId`: um novo `MemoryRecord` `Project` é criado, com `promotedFrom` apontando para o(s) registro(s) `Operational` de origem. Os registros `Operational` originais **não são apagados**.

### 4. Avaliação de generalização (`Project → LongTerm`)

Quando o mesmo `subjectKey` (ou sua forma generalizada) existe como `Project` Memory em mais de um `workspaceId`: um novo `MemoryRecord` `LongTerm` é criado, sem `workspaceId` (é cross-Workspace por definição), com `promotedFrom` apontando para os registros `Project` de origem.

## Fluxo 2 — Sincronização de Digital Twin

```
Primeiro contato com a entidade externa
      ↓
DigitalTwin criado (state inicial)
      ↓
Semantic Event chega (ex: identity.created, budget.created_via_gestor)
      ↓
state atualizado, lastSyncedAt renovado
      ↓
Consulta a um Twin fora da janela de refresh
      ↓
warning no Envelope — nunca falha silenciosamente
```

### 1. Primeiro contato

Para `subjectType: User`, o primeiro contato é o próprio evento `identity.created` — não precisa de uma Capability de leitura prévia (o Identity Engine já é a fonte). Para `Client`/`Project`/`Company`, o primeiro contato é uma Capability de leitura real (`GestorSkill.FindClient`, Release 8) — até lá, o schema existe, mas nenhum Twin desses três tipos é criado de fato (ver [MEMORY_MODEL.md — Fronteira com o Identity Engine](MEMORY_MODEL.md#fronteira-com-o-identity-engine--decisão-sobre-o-usertwin)).

### 2. Atualização por Semantic Event

Toda vez que um Semantic Event relevante ao `subjectType` chega no Event Bus, o Memory Engine atualiza `state` — nunca lê o sistema externo por conta própria fora desse gatilho, e nunca escreve no sistema externo (a escrita é sempre via Capability, [ADR-0007](docs/adr/0007-comunicacao-somente-via-api.md)).

### 3. Staleness

`lastSyncedAt` é comparado contra uma janela de refresh esperada (o valor exato é decisão de Implementation, não deste documento). Fora da janela, qualquer resposta que use aquele Twin carrega um `warning` no Envelope — o consumidor sabe que a informação pode estar desatualizada, em vez de confiar cegamente nela.

## Onde vive

Implementado por `packages/memory-engine` (Release 4), disponibilizado a todo Engine seguinte através do Kernel — mesmo padrão de disponibilização já usado pelo Identity Engine para `Context`/`Identity` ([KERNEL.md](KERNEL.md)).
