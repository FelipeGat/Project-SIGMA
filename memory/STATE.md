# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 1 — SIGMA Protocol: aprovada, push realizado.** **Release 2 — SIGMA Bootstrap: aprovada (revisão 3), implementação autorizada e em andamento.** Processo de Release passa a seguir quatro fases (Proposal → Architecture Review → Implementation → Validation — [ADR-0048](../docs/adr/0048-processo-quatro-fases.md)). Primeiro código de aplicação do Project SIGMA sendo escrito nesta mesma sessão.

## O que existe (documentação)

- Visão, produto, filosofia, horizonte de longo prazo, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md) (Module-only, interfaces `ILogger/IEventBus/IModule/IConfiguration/IHealth/IContainer`), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), demais docs estruturais.
- **Novidades desta rodada**: [COMPATIBILITY.md](../COMPATIBILITY.md), `contracts/` (Sigma Contracts), `docs/rfc/` (processo RFC), `sdk/` (php/typescript/python/docs, placeholder multi-linguagem).
- Arquitetura com **dez Engines** — [docs/architecture/ARCHITECTURE.md](../docs/architecture/ARCHITECTURE.md).
- **55 ADRs** — [docs/adr/](../docs/adr/).
- Proposta final da Release 2 (revisão 3) — [docs/releases/0002-sigma-bootstrap.md](../docs/releases/0002-sigma-bootstrap.md), escopo explícito: Bootstrap, DI Container, Module Loader, Configuration Provider, Lifecycle Manager, Health Manager, Event Bus (infra), Telemetry, System Manifest Loader — **sem banco de dados, sem domínio**.

## O que existe (código) — em construção nesta sessão

- `packages/core`, `packages/kernel`, `services/event-bus`, `services/gateway` — implementação da Release 2 em andamento.

## Pendências sinalizadas, aguardando confirmação do Product Owner

- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Não bloqueia Release 2; relevante antes da Release 6/7.

## Bloqueios

Nenhum. Implementação da Release 2 autorizada e em andamento. Ver [NEXT.md](../memory/NEXT.md).
