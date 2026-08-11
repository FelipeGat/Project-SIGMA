# services/gateway

Superfície HTTP pública do SIGMA. É o único ponto de entrada de `apps/web`, `apps/mobile` e `packages/sdk` no sistema. Cresce em conjunto com cada Engine implementado — o gateway expõe cada Engine à medida que ele existe, não de uma vez.

Cinco rotas hoje, num front controller mínimo (`public/index.php`), ainda sem Laravel — ver o porquê no [Decision Log da Release 2](../../docs/releases/0002-sigma-bootstrap-decision-log.md):

| Rota | Desde |
|---|---|
| `GET /health/live`, `/health/ready`, `/health/startup` | Release 2 — a lógica vive em `packages/kernel/src/Http/` desde a [ADR-0094](../../docs/adr/0094-health-endpoints-pertencem-ao-kernel.md) |
| `POST /missions` | Release 5.5 |
| `GET /missions/{id}` | Release 5.5 |

Registra quatro Modules (`kernel`, `event-bus`, `identity-engine`, `mission-engine`) e depende de MariaDB desde a Release 5.5. Ganha Laravel/roteamento completo quando expuser o ciclo de vida completo da Mission — escopo da Release 12.
