# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 2 — SIGMA Bootstrap: aprovada, implementada, push realizado.** **Release 3 foi dividida em duas sub-Releases sequenciais** ([ADR-0060](../docs/adr/0060-release-dividida-em-sub-releases.md)): **3A — Identity Domain** (Proposal aprovada para implementação, revisão 3) e **3B — Identity Infrastructure** (placeholder, sua Proposal só é escrita depois que 3A estiver implementada e validada). Todos os pré-requisitos de 3A estão publicados: [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md), `contracts/Identity.contract.yaml`. **Nenhum código do Identity foi escrito ainda.**

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md) (novo), `contracts/` (incl. `Identity.contract.yaml`), `docs/rfc/`, `sdk/`.
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **63 ADRs** — [docs/adr/](../docs/adr/). Novas nesta rodada: 0059 (repositório é fonte da verdade), 0060 (Release dividida em sub-Releases), 0061 (Engine em 4 camadas DDD), 0062 (Identity nunca conhece outro Engine), 0063 (identificadores como Value Object).
- Release 2 (revisão 3): Proposal, [Decision Log](../docs/releases/0002-sigma-bootstrap-decision-log.md), [Validation Report](../docs/releases/0002-sigma-bootstrap-validation-report.md).
- Release 3A (revisão 3): [Proposal](../docs/releases/0003a-identity-domain.md) — **aprovada para implementação**.
- Release 3B: [placeholder](../docs/releases/0003b-identity-infrastructure.md) — Proposal completa ainda não escrita.
- [VALIDATION_REPORT.template.md](../docs/releases/VALIDATION_REPORT.template.md) — padrão obrigatório para toda Release/sub-Release.
- `memory/README.md` agora declara explicitamente: repositório é fonte da verdade, memória de qualquer ferramenta de IA é cache de conveniência ([ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md)).

## O que existe (código) — Release 2, implementada e refinada

- `packages/core` — `Id`, `SigmaException`. 4 testes.
- `packages/kernel` — 6 interfaces, `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader` (valida `manifestVersion`), `KernelModule`, `InMemoryEventBus`. 36 testes.
- `services/event-bus` — `RedisEventBus` (compõe `InMemoryEventBus`), `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, `Envelope`, front controller. 8 testes + HTTP real.
- `contracts/Module.contract.yaml`, `contracts/Identity.contract.yaml` (sem código correspondente ainda — deliberado).
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — build ainda não verificado.
- `system-manifest.yaml` — com `manifestVersion: 1`.

**Total: 54 testes automatizados, todos passando.** Nenhum código de `packages/identity-engine` existe ainda — Release 3A é a próxima a ganhar código, escopo estritamente `Domain/` (ver [ADR-0061](../docs/adr/0061-engine-quatro-camadas-ddd.md)).

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — reconciliação adiada para a Release de CI/CD.
- **`docker-compose` não verificado por build real** — vira Critério de Aceite explícito da Release 3B (não da 3A, que não usa infraestrutura nenhuma).
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.
- Backlog sinalizado, não implementado: `/health/details`, `LogContext`, validação de Bootstrap para dependência ausente (Module → FAILED).
- Cinco ADRs de "Direção aprovada" (Identity como raiz, Session→Identity, Context imutável, Team tipado, Autonomy por capability) devem ser escritas **durante** a Implementation da Release 3A, como parte do trabalho — ainda não escritas.

## Bloqueios

Nenhum. Release 3A está liberada para começar a Implementation — só `packages/identity-engine/Domain/`, sem infraestrutura. Release 3B aguarda 3A estar implementada e validada antes de sua própria Proposal ser escrita. Ver [NEXT.md](../memory/NEXT.md).
