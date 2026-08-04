# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 2 — SIGMA Bootstrap: aprovada, implementada, push realizado (commits `87053ed` e `a57fd7a`).** **Release 3 — Identity Engine: Proposal (revisão 2) aprovada para implementação**, mediante a entrega de [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md) e `contracts/Identity.contract.yaml` — ambos já publicados. **Nenhum código do Identity foi escrito ainda.** Cinco refinamentos de direção (Identity como raiz, Session presa a Identity, Context imutável, Team tipado, Autonomy por capability) aprovados para formalizar via ADR durante a própria Implementation, sem reabrir a Proposal.

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md) (novo), `contracts/` (incl. `Identity.contract.yaml`, novo), `docs/rfc/`, `sdk/`.
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **58 ADRs** — [docs/adr/](../docs/adr/).
- Release 2 (revisão 3): Proposal, [Decision Log](../docs/releases/0002-sigma-bootstrap-decision-log.md) e [Validation Report](../docs/releases/0002-sigma-bootstrap-validation-report.md).
- Release 3 (revisão 2): [Proposal](../docs/releases/0003-identity-engine.md) — **aprovada para implementação**.
- [VALIDATION_REPORT.template.md](../docs/releases/VALIDATION_REPORT.template.md) — padrão obrigatório para toda Release.

## O que existe (código) — Release 2, implementada e refinada

- `packages/core` — `Id`, `SigmaException`. 4 testes.
- `packages/kernel` — 6 interfaces, `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader` (valida `manifestVersion`), `KernelModule`, `InMemoryEventBus`. 36 testes.
- `services/event-bus` — `RedisEventBus` (compõe `InMemoryEventBus`), `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, `Envelope`, front controller. 8 testes + HTTP real.
- `contracts/Module.contract.yaml`, `contracts/Identity.contract.yaml` (novo, sem código correspondente ainda — deliberado).
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — build ainda não verificado.
- `system-manifest.yaml` — com `manifestVersion: 1`.

**Total: 54 testes automatizados, todos passando.** Nenhum código de `packages/identity-engine`/`services/auth` existe ainda.

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — reconciliação adiada para a Release de CI/CD, decisão explícita.
- **`docker-compose` não verificado por build real** — Release 3 introduz MariaDB no compose; validar de fato vira Critério de Aceite explícito desta Release (não repetir a pendência da Release 2).
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.
- Backlog sinalizado, não implementado: `/health/details`, `LogContext`, validação de Bootstrap para dependência ausente (Module → FAILED). Ver `memory/NEXT.md`.
- Cinco ADRs ainda não escritas, a produzir **durante** a Implementation da Release 3 (não bloqueiam o início): Identity como raiz, Session→Identity, Context imutável, Team tipado, Autonomy por capability (revisita ADR-0029).

## Bloqueios

Nenhum. Release 3 está liberada para começar a Implementation. Próximo passo natural é a Architecture Review (arquitetura já esboçada na Proposal, incluindo a pergunta em aberto sobre camada de acesso a dados) seguida da escrita real do schema/código. Ver [NEXT.md](../memory/NEXT.md).
