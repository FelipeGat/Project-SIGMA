# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 3 — Identity Engine COMPLETA (3A + 3B).** Primeiro Engine de domínio real do SIGMA, do modelo puro (3A) à infraestrutura real (3B) — persistência MariaDB, autenticação real, HTTP real, `docker compose up --build` validado de fato pela primeira vez no projeto. 135 testes automatizados no monorepo, todos passando. Release 2 (SIGMA Bootstrap) segue aprovada, implementada e com push feito.

## O que existe (documentação)

- Visão, produto, filosofia, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md), `contracts/`, `docs/rfc/`, `sdk/`.
- **69 ADRs** — [docs/adr/](../docs/adr/). Novas nesta rodada: 0069 (Envelope em packages/core).
- Release 2: Proposal, Decision Log, Validation Report — completos.
- Release 3A: Proposal, [Decision Log](../docs/releases/0003a-identity-domain-decision-log.md), [Validation Report](../docs/releases/0003a-identity-domain-validation-report.md) — **implementada**.
- Release 3B: Proposal, [Decision Log](../docs/releases/0003b-identity-infrastructure-decision-log.md), [Validation Report](../docs/releases/0003b-identity-infrastructure-validation-report.md) — **implementada**.

## O que existe (código)

- `packages/core` — `Id`, `SigmaException`, **`Envelope`** (movido de `services/gateway`, ADR-0069). 8 testes.
- `packages/kernel` — 6 interfaces, `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader`, `KernelModule`, `InMemoryEventBus`. 36 testes.
- `services/event-bus` — `RedisEventBus`, `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, front controller. 8 testes + HTTP real.
- **`packages/identity-engine`** — completo nas 4 camadas:
  - `Domain/` — 9 Value Objects de ID, `Tenant/Company/Workspace/User/Team/Permission/Scope/Role/RoleAssignment/Session/Context/Identity`, 10 eventos, `reconstitute()` em Identity/Session/RoleAssignment.
  - `Application/` — 9 interfaces de repositório + `PasswordHasher` + 9 casos de uso.
  - `Infrastructure/` — `MigrationRunner`+`CreateSchema` (12 tabelas), 9 repositórios `Pdo*`, `Argon2idPasswordHasher`.
  - `Interfaces/` — `IdentityEngineModule implements IModule`.
  - 72 testes (62 em memória + 10 contra MariaDB real).
- **`services/auth`** (novo) — `Bootstrap`, `AuthEndpoints`, front controller: `POST /auth/login`, `POST /auth/logout`, `POST /auth/workspace`, `GET /auth/context`. 5 testes contra MariaDB real.
- `contracts/Module.contract.yaml`, `contracts/Identity.contract.yaml`.
- `docker/docker-compose.yml` (redis+mariadb+gateway+auth) + `docker/gateway.Dockerfile` + `docker/auth.Dockerfile` — **build e boot validados de fato via `docker compose up --build`**.
- `system-manifest.yaml` — `kernel`, `event-bus`, `identity-engine` (optional: true — ver Decision Log da 3B).

**Total: 135 testes automatizados, todos passando** (8+36+6+8+72+5).

## Achados reais desta rodada (todos corrigidos)

- `Identity`/`Session`/`RoleAssignment` precisaram de `reconstitute()` — hidratar do banco sem redisparar eventos de domínio.
- `Team` ganhou `members(): array` (repositório precisava persistir a lista).
- `IdentityEngineModule::register()` vazava `\PDOException` crua — corrigido para `SigmaException`.
- `AuthEndpoints::guarded()` só capturava `SigmaException` — uma falha de infraestrutura quebrava o Envelope. Corrigido para capturar `\Throwable`.
- `system-manifest.yaml` compartilhado por `gateway`(kernel+event-bus) e `auth`(+identity-engine) — `identity-engine` precisou ser `optional: true` para não quebrar o boot do `gateway`.
- `Interface/` como nome de pasta/namespace é inválido (`interface` é palavra reservada do PHP) — usado `Interfaces/`.

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — adiado para a Release de CI/CD.
- Nenhum limite de Sessions concorrentes por Identity — sinalizado, não decidido (ADR-0065).
- Divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) — reconciliação adiada para o Skill Engine (Release 8), ver ADR-0068.
- `PermissionId` (Value Object) sem uso na Infrastructure — `Permission` usa chave string natural. Registrado, não removido.
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.
- Backlog sinalizado, não implementado: `/health/details`, `LogContext`, validação de Bootstrap para dependência ausente.

## Bloqueios

Nenhum. Push do commit final desta rodada aguardando confirmação. Próximo passo natural: Release 4 — Memory Engine. Ver [NEXT.md](../memory/NEXT.md).
