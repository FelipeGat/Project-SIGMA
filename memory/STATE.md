# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 2 — SIGMA Bootstrap: aprovada, implementada, push realizado (commit `87053ed`).** Refinamentos pré-Release-3 concluídos (EventBus por composição, `manifestVersion`, `VALIDATION_REPORT.md` como padrão). **Release 3 — Identity Engine: Proposal apresentada (revisão 1), aguardando aprovação.** `IDENTITY_MODEL.md` publicado — nenhuma linha de código do Identity é escrita antes de duas aprovações separadas e explícitas: o modelo e a Proposal.

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md) (novo), `contracts/`, `docs/rfc/`, `sdk/`.
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **58 ADRs** — [docs/adr/](../docs/adr/) (0056 VALIDATION_REPORT.md obrigatório, 0057 EventBus por composição, 0058 manifestVersion).
- Release 2 (revisão 3): Proposal, [Decision Log](../docs/releases/0002-sigma-bootstrap-decision-log.md) e **[Validation Report](../docs/releases/0002-sigma-bootstrap-validation-report.md)** (novo, retroativo).
- Release 3: [Proposal](../docs/releases/0003-identity-engine.md) (revisão 1) — aguardando aprovação.
- [VALIDATION_REPORT.template.md](../docs/releases/VALIDATION_REPORT.template.md) — padrão obrigatório para toda Release a partir de agora.

## O que existe (código) — Release 2, implementada e refinada

- `packages/core` — `Id`, `SigmaException`. 4 testes.
- `packages/kernel` — 6 interfaces (`IContainer`/`ILogger`/`IEventBus`/`IModule`/`IConfiguration`/`IHealth`), `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader` (agora valida `manifestVersion`), `KernelModule`, **`InMemoryEventBus`** (novo — pub/sub local puro). 36 testes.
- `services/event-bus` — `RedisEventBus` (agora compõe `InMemoryEventBus` para entrega local, publica no Redis via `RedisPublisher`), `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, `Envelope`, front controller. 8 testes + validação HTTP real (`/health/live`, `/health/ready`, `/health/startup`, 404), reconfirmada após o refinamento.
- `contracts/Module.contract.yaml` — primeiro Sigma Contract real.
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — escritos, **build ainda não verificado** (ver Validation Report da Release 2).
- `system-manifest.yaml` — agora com `manifestVersion: 1`.

**Total: 54 testes automatizados, todos passando.**

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — decisão explícita do Product Owner de não reconciliar agora; fica para a Release de CI/CD.
- **`docker-compose` não verificado por build real** — aceito conscientemente, condicionado ao registro no Validation Report da Release 2.
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Não bloqueia nada agora; relevante antes da Release 6/7.
- Observações registradas mas não implementadas nesta rodada (não estavam nas "quatro entregas" explícitas): `/health/details`, `LogContext`, validação de Bootstrap para dependência declarada mas inexistente (Module → FAILED). Ver `memory/DECISIONS.md`.

## Bloqueios

**Release 3 não pode ganhar nenhuma linha de código do Identity até duas aprovações separadas do Product Owner**: (1) [IDENTITY_MODEL.md](../IDENTITY_MODEL.md); (2) [docs/releases/0003-identity-engine.md](../docs/releases/0003-identity-engine.md). Restrição explícita, verbatim do Product Owner. Ver [NEXT.md](../memory/NEXT.md).
