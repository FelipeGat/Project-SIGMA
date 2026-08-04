# Release 4B — Memory Infrastructure

Segunda metade da Release 4 — Memory Engine ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)). **Não é uma Proposal ainda** — este documento é um placeholder deliberado, mesmo padrão usado por [0003b-identity-infrastructure.md](0003b-identity-infrastructure.md) antes de a Release 3A existir em código. A Proposal completa (mesmo formato de [0004a-memory-domain.md](0004a-memory-domain.md)) só é escrita depois que a Release 4A — Memory Domain estiver implementada e validada.

## O que já se sabe

- Camadas `Application/`, `Infrastructure/`, `Interfaces/` de `packages/memory-engine` (ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)), consumindo o `Domain/` já validado por 4A.
- Persistência real (MariaDB) de `MemoryRecord`/`KnowledgeRecord`/`DigitalTwin`.
- Consumo real de eventos Semantic do Event Bus — a partir dos eventos que o Identity Engine já publica (`identity.created`, `workspace.selected`) para sincronizar `UserTwin` de fato ([ADR-0079](../adr/0079-usertwin-desde-a-release-4.md)).
- Indexação real de `/knowledge` como primeira fonte de `KnowledgeRecord` — busca textual simples, sem semântica ([ADR-0080](../adr/0080-knowledge-release4-indice-simples.md)).
- `MemoryEngineModule implements IModule` — segundo `IModule` de domínio real do SIGMA (depois de `IdentityEngineModule`), registrado no System Manifest.
- `docker-compose.yml` — `memory-engine` provavelmente reaproveita o `mariadb` já existente (schema novo, banco/container não necessariamente novo) — a confirmar na Architecture Review de 4B.
- Decision Log e Validation Report próprios.

## O que ainda não está decidido

- Se `EvaluatePromotion`/sincronização de Twin ganham algum tipo de API própria (`services/memory`?) ou se, nesta Release, só existem como Application consumida internamente (via Event Bus/Container), sem casca HTTP dedicada ainda — a confirmar quando 4A estiver validada.
- Onde exatamente a lógica de detecção de staleness de Twin dispara o `warning` no Envelope (responsabilidade de quem consome o Twin, não necessariamente do Memory Engine em si) — precisa de decisão explícita.
- Se `packages/identity-engine/src/Domain/Identifier.php` migra para `packages/core` (pergunta já registrada na Proposal de 4A) — resolvida antes de 4B, idealmente, para não duplicar a base de Value Object de identificador.

## Quando esta Proposal é escrita de fato

Assim que a [Release 4A — Memory Domain](0004a-memory-domain.md) tiver Decision Log e Validation Report publicados.
