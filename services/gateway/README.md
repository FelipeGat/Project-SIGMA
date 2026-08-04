# services/gateway

Superfície HTTP/WebSocket pública do SIGMA — a aplicação Laravel que monta `packages/kernel` e os demais Engines por trás de rotas de API REST e canais WebSocket. É o único ponto de entrada de `apps/web`, `apps/mobile` e `packages/sdk` no sistema.

Vazio na Fase Foundation. Cresce em conjunto com cada Engine implementado a partir da Release 2 do [ROADMAP.md](../../ROADMAP.md) — o gateway expõe cada Engine à medida que ele existe, não de uma vez.
