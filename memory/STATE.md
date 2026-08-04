# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 1 — SIGMA Protocol: aprovada, push realizado. Release 2 — SIGMA Bootstrap: implementada.** Primeiro código de aplicação do Project SIGMA — 48 testes automatizados passando, validado com HTTP real via `php -S`. Commitado localmente, **push ainda não realizado** — aguardando confirmação explícita antes de enviar o código ao GitHub (distinto da aprovação de push das rodadas de documentação anteriores, já enviadas).

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), `contracts/`, `docs/rfc/`, `sdk/`.
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **55 ADRs** — [docs/adr/](../docs/adr/).
- Proposta final da Release 2 (revisão 3) e **Decision Log** — [docs/releases/0002-sigma-bootstrap.md](../docs/releases/0002-sigma-bootstrap.md), [docs/releases/0002-sigma-bootstrap-decision-log.md](../docs/releases/0002-sigma-bootstrap-decision-log.md).

## O que existe (código) — Release 2, implementada

- `packages/core` — `Id`, `SigmaException`. 4 testes.
- `packages/kernel` — 6 interfaces (`IContainer`/`ILogger`/`IEventBus`/`IModule`/`IConfiguration`/`IHealth`), `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader`, `KernelModule`. 30 testes.
- `services/event-bus` — `RedisEventBus`, `EventBusModule`. 6 testes.
- `services/gateway` — `Bootstrap`, `HealthEndpoints`, `Envelope`, front controller (`public/index.php`, sem Laravel nesta Release — ver Decision Log). 8 testes + validação HTTP real (`/health/live`, `/health/ready`, `/health/startup`, 404).
- `contracts/Module.contract.yaml` — primeiro Sigma Contract real.
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — escritos, **build não verificado** (Docker Desktop indisponível na sessão — ver Decision Log).
- `system-manifest.yaml` de exemplo na raiz.

**Total: 48 testes automatizados, todos passando.**

## Pendências / riscos sinalizados

- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Não bloqueia Release 2; relevante antes da Release 6/7.
- **PHP local é 8.2, ADR-0009 pede 8.4** — código compatível com 8.2 (testado de fato), mas produção/CI precisam rodar 8.4 antes do deploy — não validado nesta sessão.
- **`docker-compose` não verificado por build real** — ver Decision Log da Release 2.

## Bloqueios

Nenhum. Aguardando confirmação para dar push do código da Release 2. Ver [NEXT.md](../memory/NEXT.md).
