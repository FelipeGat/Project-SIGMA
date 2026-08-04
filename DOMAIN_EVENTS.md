# Domain Events

Catálogo dos eventos de domínio que cada Engine publica — o contrato pelo qual o resto do SIGMA sabe o que aconteceu, sem nunca precisar conhecer o Engine que publicou (ver [ADR-0062](docs/adr/0062-identity-nunca-conhece-outro-engine.md)). Distinto do catálogo de [EVENT_MODEL.md](EVENT_MODEL.md), que cobre a sequência **Technical** de orquestração de uma Mission entre os Engines do núcleo — este documento cobre eventos de **domínio** (camada Semantic, ver [EVENT_MODEL.md — Três camadas de evento](EVENT_MODEL.md#três-camadas-de-evento)), publicados por um Engine específico sobre mudanças no seu próprio agregado. Entrega obrigatória antes de qualquer código da Release 3, junto de [IDENTITY_MODEL.md](IDENTITY_MODEL.md) e [IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md).

Desde a Release 3.5, [EVENT_CATALOG.md](EVENT_CATALOG.md) é a referência para **listar** todo evento do SIGMA de uma vez (com quem consome, versão, contrato) — este documento continua sendo a referência para entender o porquê de cada evento de Identity existir. As duas tabelas devem sempre bater; se divergirem, é um bug de documentação a corrigir, não uma escolha entre uma ou outra.

## Identity Engine

Todos na camada **Semantic** — fatos de negócio sobre o agregado `Identity` (ver [IDENTITY_LIFECYCLE.md](IDENTITY_LIFECYCLE.md)), não passos de orquestração de Mission. Consumidores esperados: Memory Engine (atualiza o Digital Twin de User/Company), Audit Engine (trilha de conformidade) — nenhum dos dois é conhecido pelo Identity Engine no momento da publicação (ver [ADR-0062](docs/adr/0062-identity-nunca-conhece-outro-engine.md)).

| Evento | Nome no Event Bus | Publicado quando | Payload mínimo |
|---|---|---|---|
| `IdentityCreated` | `identity.created` | Um User ganha existência no sistema (Identity Lifecycle, etapa 1) | `identityId`, `userId`, `tenantId` |
| `IdentityActivated` | `identity.activated` | Uma Identity antes inativa passa a poder se autenticar | `identityId` |
| `IdentityDisabled` | `identity.disabled` | Uma Identity é desativada — sessões vigentes devem ser invalidadas por quem consome este evento | `identityId`, `reason` |
| `SessionStarted` | `session.started` | Uma Session é emitida após autenticação bem-sucedida (Identity Lifecycle, etapa 4) | `sessionId`, `identityId`, `issuedAt`, `expiresAt` |
| `SessionEnded` | `session.ended` | Uma Session é invalidada — por logout explícito ou por expiração | `sessionId`, `identityId`, `reason` (`logout` \| `expired`) |
| `WorkspaceSelected` | `workspace.selected` | Um Workspace é escolhido como ativo dentro de uma Session (Identity Lifecycle, etapa 5) | `sessionId`, `identityId`, `workspaceId` |
| `RoleAssigned` | `role.assigned` | Um `RoleAssignment` é criado — User ou Team recebe um Role num escopo (ver [IDENTITY_MODEL.md](IDENTITY_MODEL.md#relações)) | `roleId`, `subjectType` (`user` \| `team`), `subjectId`, `scopeType`, `scopeId` |
| `RoleRevoked` | `role.revoked` | Um `RoleAssignment` é removido | `roleId`, `subjectType`, `subjectId`, `scopeType`, `scopeId` |
| `PermissionGranted` | `permission.granted` | Um Role passa a conceder uma Permission que antes não concedia | `roleId`, `permissionKey` |
| `PermissionRevoked` | `permission.revoked` | Um Role deixa de conceder uma Permission que antes concedia | `roleId`, `permissionKey` |

`RoleRevoked` foi adicionado por simetria com `RoleAssigned` — não estava na lista original do Product Owner, mas é indissociável dela: todo `RoleAssignment` que pode ser criado precisa poder ser desfeito, e o evento correspondente precisa existir desde já para quem for consumi-lo (ex: Audit Engine).

## Memory Engine

Todos na camada **Semantic** — fatos sobre o que o Memory Engine observou, promoveu, deprecou, retratou ou sincronizou (ver [MEMORY_MODEL.md](MEMORY_MODEL.md), [MEMORY_LIFECYCLE.md](MEMORY_LIFECYCLE.md), [MEMORY_PROMOTION_RULES.md](MEMORY_PROMOTION_RULES.md)). Escrito antes do código, mesmo padrão já usado para Identity antes da Release 3A — entrega obrigatória antes de qualquer código da Release 4. Consumidor esperado: Audit Engine (trilha de conformidade sobre o que o SIGMA aprendeu e quando), e um User com `knowledge.curate` no caso de `MemoryPromoted` com `toLevel: LongTerm` (candidatura a Knowledge, ver [MEMORY_MODEL.md — Fronteira com Knowledge](MEMORY_MODEL.md#fronteira-com-knowledge--candidatura-nunca-promoção-automática)). Revisão 2 — cinco eventos novos ([ADR-0083](docs/adr/0083-contextmemory-como-estagio-pre-memory.md), [ADR-0088](docs/adr/0088-retracao-expiracao-e-governanca-de-promocao.md)), `KnowledgeRecordIndexed` ganha `version` no payload ([ADR-0086](docs/adr/0086-knowledgerecord-imutavel-e-versionado.md)).

| Evento | Nome no Event Bus | Publicado quando | Payload mínimo |
|---|---|---|---|
| `ContextMemoryStarted` | `context_memory.started` | Um `ContextMemory` é aberto no início de um engajamento | `contextMemoryId`, `workspaceId`, `missionId` (opcional), `origin` |
| `ContextMemoryClosed` | `context_memory.closed` | Um `ContextMemory` é fechado ao fim do engajamento, disparando a destilação | `contextMemoryId`, `workspaceId`, `endedAt` |
| `MemoryRecordObserved` | `memory.record_observed` | Um `MemoryRecord` `Operational` é criado a partir da destilação de um `ContextMemory` fechado | `memoryRecordId`, `subjectKey`, `workspaceId`, `missionId`, `confidence`, `origin` |
| `MemoryPromoted` | `memory.promoted` | Um `MemoryRecord` é promovido a um nível mais alto (`Operational→Project` ou `Project→LongTerm`) | `memoryRecordId`, `subjectKey`, `fromLevel`, `toLevel`, `confidence`, `promotedFrom` |
| `MemoryDeprecated` | `memory.deprecated` | Um `MemoryRecord` `Active` é automaticamente marcado `Deprecated` por uma observação contraditória | `memoryRecordId`, `subjectKey`, `contradictedBy` |
| `MemoryRetracted` | `memory.retracted` | Um `MemoryRecord` é marcado `Retracted` por ação humana explícita | `memoryRecordId`, `subjectKey`, `actor` |
| `MemorySubjectPinned` | `memory.subject_pinned` | Um `subjectKey` é fixado por ação humana explícita para nunca promover automaticamente | `subjectKey`, `workspaceId`, `actor` |
| `KnowledgeRecordIndexed` | `knowledge.indexed` | Um `KnowledgeRecord` é criado — nova versão — a partir de `/knowledge` | `knowledgeRecordId`, `area`, `sourcePath`, `version` |
| `DigitalTwinCreated` | `digital_twin.created` | Um `DigitalTwin` é criado — sempre a partir de um evento, nunca de leitura direta ([ADR-0085](docs/adr/0085-digital-twin-estritamente-event-driven.md)) | `digitalTwinId`, `subjectType`, `externalRef` |
| `DigitalTwinUpdated` | `digital_twin.updated` | O `state` de um `DigitalTwin` é atualizado a partir de um Semantic Event | `digitalTwinId`, `subjectType`, `lastSyncedAt` |
| `DigitalTwinStale` | `digital_twin.stale` | Um `DigitalTwin` é consultado fora da janela de refresh esperada | `digitalTwinId`, `subjectType`, `lastSyncedAt` |

## Regra de payload

Todo evento desta lista carrega, além do payload mínimo indicado, os campos padrão de correlação já exigidos por [EVENT_MODEL.md — Regras](EVENT_MODEL.md#regras): identificador de origem, timestamp, Engine publicador. `permissionKey` refere-se sempre à chave string definida em [IDENTITY_MODEL.md#permission](IDENTITY_MODEL.md#permission) (ex: `mission.create`), nunca a um identificador interno de linha de banco.

## O que este documento não decide

- O schema de serialização exato de cada payload (JSON Schema por evento) — nasce durante a Implementation da Release 3A, junto do código que publica cada evento.
- Se algum destes eventos, com o tempo, é promovido a Business (ver [EVENT_MODEL.md](EVENT_MODEL.md)) — essa curadoria é decisão do Automation Engine (Release 14)/Analytics (Release 15), não deste documento.

## Onde vive

Publicado por `packages/identity-engine/Domain/` (ver [ADR-0061](docs/adr/0061-engine-quatro-camadas-ddd.md)) sobre `IEventBus` ([packages/kernel](packages/kernel/)) — nunca conhecendo quem consome, conforme [ADR-0062](docs/adr/0062-identity-nunca-conhece-outro-engine.md).
