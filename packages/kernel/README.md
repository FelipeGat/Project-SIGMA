# packages/kernel

Implementação do Kernel do SIGMA. Ver [KERNEL.md](../../KERNEL.md) para o escopo conceitual completo (o que pertence e o que nunca pertence). A Release 2 — SIGMA Bootstrap entrega o primeiro incremento: Config, Logger, DI Container, Modules, Events, Lifecycle, Health — ver [BOOTSTRAP.md](../../BOOTSTRAP.md) e [ADR-0038](../../docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md). Carregamento do Plugin System e contexto de Tenant/Workspace chegam em Releases posteriores.

**Implementado na Release 2** — `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader`, `KernelModule`, e as seis interfaces em `Contract/`. 30 testes automatizados. Ver [ROADMAP.md](../../ROADMAP.md) e o [Decision Log da Release 2](../../docs/releases/0002-sigma-bootstrap-decision-log.md).
