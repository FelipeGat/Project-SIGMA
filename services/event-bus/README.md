# services/event-bus

Wrapper do backbone de eventos do SIGMA — Redis (filas, pub/sub, broadcasting), usado por todos os Engines para publicar e assinar eventos de domínio (ver [EVENT_MODEL.md](../../EVENT_MODEL.md) e [ADR-0008](../../docs/adr/0008-arquitetura-orientada-a-eventos.md)). Nenhum Engine fala com Redis diretamente — sempre através deste serviço.

**Implementado na Release 2** — `RedisEventBus` (implementa `IEventBus` sobre `predis/predis`) e `EventBusModule`. 6 testes automatizados. Listener Redis cross-processo real ainda não implementado — simplificação conhecida, ver o [Decision Log da Release 2](../../docs/releases/0002-sigma-bootstrap-decision-log.md).
