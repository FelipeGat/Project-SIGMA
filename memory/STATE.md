# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 2 — SIGMA Bootstrap: aprovada, implementada, push realizado.** **Release 3A — Identity Domain: implementada.** Primeiro código do Identity Engine — domínio puro, sem persistência, sem HTTP, sem nenhuma dependência de infraestrutura. 50 testes automatizados passando. **Release 3B — Identity Infrastructure: ainda placeholder**, Proposal completa a escrever agora que 3A está implementada e validada.

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md), `contracts/` (incl. `Identity.contract.yaml`), `docs/rfc/`, `sdk/`.
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **68 ADRs** — [docs/adr/](../docs/adr/). Novas nesta rodada: 0064 (Identity como agregado raiz), 0065 (Session autentica Identity), 0066 (Context imutável), 0067 (Team tipado), 0068 (Autonomy por capability).
- Release 2 (revisão 3): Proposal, [Decision Log](../docs/releases/0002-sigma-bootstrap-decision-log.md), [Validation Report](../docs/releases/0002-sigma-bootstrap-validation-report.md).
- Release 3A (revisão 3): [Proposal](../docs/releases/0003a-identity-domain.md), [Decision Log](../docs/releases/0003a-identity-domain-decision-log.md), [Validation Report](../docs/releases/0003a-identity-domain-validation-report.md) — **implementada**.
- Release 3B: [placeholder](../docs/releases/0003b-identity-infrastructure.md) — Proposal completa ainda não escrita (próximo passo natural agora).

## O que existe (código)

- `packages/core` — `Id`, `SigmaException`. 4 testes.
- `packages/kernel` — 6 interfaces, `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader` (valida `manifestVersion`), `KernelModule`, `InMemoryEventBus`. 36 testes.
- `services/event-bus` — `RedisEventBus` (compõe `InMemoryEventBus`), `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, `Envelope`, front controller. 8 testes + HTTP real.
- **`packages/identity-engine/src/Domain/` (novo)** — `Identifier` + 9 IDs (`TenantId`...`IdentityId`), enums (`TeamType`/`ScopeType`/`SubjectType`), `Tenant`/`Company`/`Workspace`/`User`/`Team`/`Permission`/`Scope`/`Role`/`RoleAssignment`/`Session`/`Context`/`Identity` (agregado raiz), 10 classes de evento de domínio + `RecordsDomainEvents`. 50 testes. **Sem `Application/`/`Infrastructure/`/`Interface/` ainda** — isso é a Release 3B.
- `contracts/Module.contract.yaml`, `contracts/Identity.contract.yaml`.
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — build ainda não verificado (fica para 3B, que introduz MariaDB).
- `system-manifest.yaml` — com `manifestVersion: 1`. `identity-engine` ainda não está listado nele (só entra quando ganhar um `IModule`, na 3B).

**Total: 104 testes automatizados, todos passando** (4+36+6+8+50).

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — reconciliação adiada para a Release de CI/CD.
- **`docker-compose` não verificado por build real** — vira Critério de Aceite explícito da Release 3B.
- Divergência entre `autonomy_level_required` (inteiro, infraestrutura) e `autonomyCapabilities` (mapa nomeado, Identity Engine) — sinalizada em [ADR-0068](../docs/adr/0068-autonomy-por-capability.md), reconciliação adiada para o Skill Engine (Release 8).
- `IDENTITY_MODEL.md` ainda descreve `Context` como objeto de mais alto nível, não `Identity` — divergência documentada em [ADR-0064](../docs/adr/0064-identity-como-agregado-raiz.md), texto original não editado retroativamente (por princípio).
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.
- Backlog sinalizado, não implementado: `/health/details`, `LogContext`, validação de Bootstrap para dependência ausente (Module → FAILED).

## Bloqueios

Nenhum. Próximo passo natural: escrever a Proposal completa da Release 3B — Identity Infrastructure (persistência, `IdentityEngineModule`, `services/auth`). Ver [NEXT.md](../memory/NEXT.md).
