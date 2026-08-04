# services/gateway

Superfície HTTP pública do SIGMA. É o único ponto de entrada de `apps/web`, `apps/mobile` e `packages/sdk` no sistema. Cresce em conjunto com cada Engine implementado — o gateway expõe cada Engine à medida que ele existe, não de uma vez.

**Implementado na Release 2** — apenas os três endpoints de health (`/health/live`, `/health/ready`, `/health/startup`), como front controller mínimo (`public/index.php`), sem Laravel — ver o porquê no [Decision Log da Release 2](../../docs/releases/0002-sigma-bootstrap-decision-log.md). 8 testes automatizados + validação HTTP real via `php -S`. Ganha Laravel/roteamento completo quando tiver uma API de domínio real para expor.
