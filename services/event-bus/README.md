# services/event-bus

Wrapper do backbone de eventos do SIGMA — Redis (filas, pub/sub, broadcasting), usado por todos os Engines para publicar e assinar eventos de domínio (ver [EVENT_MODEL.md](../../EVENT_MODEL.md) e [ADR-0008](../../docs/adr/0008-arquitetura-orientada-a-eventos.md)). Nenhum Engine fala com Redis diretamente — sempre através deste serviço.

Vazio na Fase Foundation. Nasce junto com a Release 2 — SIGMA Bootstrap do [ROADMAP.md](../../ROADMAP.md) (mecanismo de Events do [BOOTSTRAP.md](../../BOOTSTRAP.md)), já que todo Engine seguinte depende dele para se comunicar.
