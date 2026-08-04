# Memory Engine — Version

Semantic Versioning por Engine ([ADR-0077](../../docs/adr/0077-version-md-e-semver-por-engine.md)) — mesmo padrão de `packages/identity-engine/VERSION.md`.

## Versão atual

**1.0.0** — já era o valor declarado em `composer.json` desde a Release 4A; este documento formaliza o que essa versão significa.

## O que está em 1.0.0

- **Domain** (Release 4A): `Identifier` + 7 Value Objects de identificador, 4 enums, `DistilledFact`, os quatro Aggregates (`ContextMemory`/`MemoryRecord`/`KnowledgeRecord`/`DigitalTwin`), 12 eventos de domínio.
- **Application** (Release 4B): 5 interfaces de repositório, `PinnedMemorySubject`, 13 casos de uso.
- **Infrastructure** (Release 4B): `MigrationRunner`, 5 repositórios `Pdo*`, `KnowledgeFolderIndexer`.
- **Interfaces** (Release 4B): `MemoryEngineModule implements IModule`.
- **`services/event-bus`** ganhou `RedisSubscriber` + `RedisEventBus::dispatchLocally()` como parte desta entrega — primeiro listener Redis cross-processo real do projeto, não exclusivo do Memory Engine mas construído para ele.
- **`services/memory-worker`** — novo serviço deployável, sem HTTP.

## Eventos publicados nesta versão

Ver [EVENT_CATALOG.md — Memory Engine](../../EVENT_CATALOG.md#memory-engine) — todos em `v1`. Doze eventos, um deles (`MemoryReactivated`) catalogado durante a própria Implementation da 4A (achado real, ver Decision Log da 4A).

## Contracts desta versão

- [contracts/Memory.contract.yaml](../../contracts/Memory.contract.yaml) — `version: "1.0.0"`, `supported_versions: ["1.0.0"]`.

## Breaking Changes

Nenhuma ainda — primeira versão.

## Política de versionamento

Mesma de `packages/identity-engine/VERSION.md`: PATCH para correção sem mudar contrato público; MINOR para capacidade nova aditiva; MAJOR para mudança incompatível em `Application/` ou no Contract.
