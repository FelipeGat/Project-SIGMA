# Memory Lifecycle

Como um engajamento bruto vira `ContextMemory`, como ele é destilado em `MemoryRecord`, como este é promovido entre níveis, como um `MemoryRecord` `LongTerm` vira candidato a `KnowledgeRecord`, e como um `DigitalTwin` se mantém sincronizado — o equivalente, para Memory, do que [IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md) é para Identity. Pré-requisito para a implementação da Release 4, ao lado de [MEMORY_MODEL.md](MEMORY_MODEL.md).

**Revisão 2** — incorpora `ContextMemory` como novo primeiro estágio, o gate de `confidence` na promoção, e a candidatura (não promoção automática) de `MemoryRecord` `LongTerm` a Knowledge. Ver [MEMORY_MODEL.md](MEMORY_MODEL.md) para a justificativa completa de cada mudança.

## Fluxo 1 — Do engajamento ao MemoryRecord

```
Conversation em andamento (Mission, ou pré-Mission)
      ↓
ContextMemory criado (status: Active)
      ↓
Engajamento termina
      ↓
ContextMemory fechado (status: Closed)
      ↓
Destilação — avalia rawContent, atribui confidence e origin
      ↓ (zero ou mais fatos dignos de registro)
MemoryRecord criado (Operational)
```

### 1. `ContextMemory` criado

No início de um engajamento (uma Conversation dentro de uma Mission, ou uma troca ainda pré-Mission), o Memory Engine abre um `ContextMemory` com `status: Active` — `tenantId`/`workspaceId` sempre presentes, `missionId` só quando já existe uma Mission associada, `origin` identificando o canal (ver [MEMORY_MODEL.md — MemoryRecord](MEMORY_MODEL.md#memoryrecord) para a lista de valores conhecidos). `rawContent` acumula o que está sendo trocado, sem estrutura imposta.

### 2. `ContextMemory` fechado

Quando o engajamento termina (Mission concluída, ou timeout de inatividade — critério exato de Implementation), `status` muda para `Closed` e `endedAt` é fixado. Um `ContextMemory` fechado é imutável a partir daqui.

### 3. Destilação

O fechamento dispara a destilação: o Memory Engine avalia `rawContent` e decide se há fato(s) dignos de virar `MemoryRecord`. Cada fato extraído recebe `subjectKey`, `content`, `confidence` (quão confiável o Memory Engine considera esse fato, ver [MEMORY_PROMOTION_RULES.md](MEMORY_PROMOTION_RULES.md#quando-uma-memory-nasce) para o piso mínimo de criação) e `origin` (copiado do `ContextMemory`). Um engajamento pode gerar zero, um, ou vários `MemoryRecord` — não é uma tradução um-para-um. `ContextMemory` não é apagado neste passo; segue sua própria política de expiração ([MEMORY_PROMOTION_RULES.md](MEMORY_PROMOTION_RULES.md#quando-uma-memory-expira)).

### 4. `MemoryRecord` criado como `Operational`

Nível mais baixo, vida útil ligada à Mission de origem — ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md). Neste ponto, o fato é só uma observação isolada, sem nenhuma confirmação de que se repete, já carregando `confidence` e `origin`.

## Fluxo 2 — Promoção de Memory

```
MemoryRecord (Operational), com confidence
      ↓
Mesmo subjectKey, mesmo Workspace, Mission diferente? E confidence ≥ piso?
      ↓ sim (repetição + confiança suficiente)
Promovido a Project (sourceMissionIds acumulado, promotedFrom preenchido)
      ↓
Mesmo subjectKey (ou generalizado), Workspace diferente? E confidence ≥ piso mais alto?
      ↓ sim (generalização + confiança suficiente)
Promovido a Long Term
      ↓
Evento MemoryPromoted (toLevel: LongTerm) — sinaliza candidato a Knowledge, nunca cria KnowledgeRecord sozinho
```

### 1. Avaliação de repetição (`Operational → Project`)

Quando `EvaluatePromotion` roda (sob demanda nesta Release — ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-a-promoção-funciona)) e encontra o mesmo `subjectKey` associado a mais de uma `missionId` dentro do mesmo `workspaceId`, **e** `confidence` está no piso definido em [MEMORY_PROMOTION_RULES.md](MEMORY_PROMOTION_RULES.md#quando-uma-memory-sobe) ou acima dele: um novo `MemoryRecord` `Project` é criado, com `promotedFrom` apontando para o(s) registro(s) `Operational` de origem. Os registros `Operational` originais **não são apagados**. Se a repetição existe mas `confidence` está abaixo do piso, a promoção não acontece — o registro permanece `Operational`, sujeito a reavaliação numa próxima chamada.

### 2. Avaliação de generalização (`Project → LongTerm`)

Quando o mesmo `subjectKey` (ou sua forma generalizada) existe como `Project` Memory em mais de um `workspaceId`, **e** `confidence` está no piso mais alto exigido para este salto: um novo `MemoryRecord` `LongTerm` é criado, sem `workspaceId` (é cross-Workspace por definição), com `promotedFrom` apontando para os registros `Project` de origem.

### 3. Candidatura a Knowledge

A criação de um `MemoryRecord` `LongTerm` publica `MemoryPromoted` (`toLevel: LongTerm`) — este evento é o sinal de que o fato é candidato a virar Knowledge (ver [MEMORY_MODEL.md — Fronteira com Knowledge](MEMORY_MODEL.md#fronteira-com-knowledge--candidatura-nunca-promoção-automática)). O Memory Engine nunca cria nem versiona um `KnowledgeRecord` a partir deste evento sozinho — a transformação exige ação humana explícita via `/knowledge`, mesmo fluxo de sempre.

## Fluxo 3 — Sincronização de Digital Twin

```
Evento causa a primeira necessidade de conhecer a entidade
      ↓
(User: identity.created já basta / Client-Project-Company: Capability publica um evento de leitura)
      ↓
DigitalTwin criado (state inicial) — sempre via Evento → Projection → Twin
      ↓
Semantic Event chega (ex: identity.created, budget.created_via_gestor)
      ↓
state atualizado, lastSyncedAt renovado — sempre via Evento → Projection → Twin
      ↓
Consulta a um Twin fora da janela de refresh
      ↓
warning no Envelope — nunca falha silenciosamente
```

### 1. Primeira população, sempre via evento

Para `subjectType: User`, o primeiro contato é o próprio evento `identity.created` — não precisa de uma Capability de leitura prévia (o Identity Engine já é a fonte). Para `Client`/`Project`/`Company`, a primeira leitura via Capability (`GestorSkill.FindClient`, Release 8) **publica um evento próprio antes de criar o Twin** — nunca escreve o resultado da leitura diretamente no Twin. Ver [MEMORY_MODEL.md — Como o Digital Twin é sincronizado](MEMORY_MODEL.md#como-o-digital-twin-é-sincronizado) para o porquê deste invariante não ter exceção.

### 2. Atualização por Semantic Event

Toda vez que um Semantic Event relevante ao `subjectType` chega no Event Bus, o Memory Engine projeta a mudança e atualiza `state` — nunca lê o sistema externo por conta própria fora desse gatilho, e nunca escreve no sistema externo (a escrita é sempre via Capability, [ADR-0007](docs/adr/0007-comunicacao-somente-via-api.md)).

### 3. Staleness

`lastSyncedAt` é comparado contra uma janela de refresh esperada (o valor exato é decisão de Implementation, não deste documento). Fora da janela, qualquer resposta que use aquele Twin carrega um `warning` no Envelope — o consumidor sabe que a informação pode estar desatualizada, em vez de confiar cegamente nela.

## Onde vive

Implementado por `packages/memory-engine` (Release 4), disponibilizado a todo Engine seguinte através do Kernel — mesmo padrão de disponibilização já usado pelo Identity Engine para `Context`/`Identity` ([KERNEL.md](KERNEL.md)).
