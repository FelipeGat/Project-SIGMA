# ADR-0057: `RedisEventBus` compõe `InMemoryEventBus` em vez de reimplementar entrega local

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

A implementação original de `RedisEventBus` (Release 2) fazia duas coisas na mesma classe: publicava no canal Redis (entrega entre processos) e mantinha seu próprio mapa de handlers para entrega síncrona local — registrado no Decision Log da Release 2 como simplificação conhecida, já que nenhum consumidor cross-processo existe ainda. Isso deixava a entrega local, que não tem nada de específico do Redis, amarrada a uma classe cujo nome promete Redis. Toda implementação futura de `IEventBus` que também precise de fallback local em processo único (`RabbitMQEventBus`, `KafkaEventBus`) reimplementaria o mesmo mapa de handlers, violando DRY e criando N cópias do mesmo bug em potencial.

`IEventBus` já era, desde a Release 2, a única interface que qualquer consumidor depende (`packages/kernel/src/Contract/IEventBus.php`) — nenhum código fora de `services/event-bus` conhece Redis diretamente. O que faltava não era a interface (já existia), era a implementação de referência puramente local que as implementações com alcance real deveriam compor.

## Decisão

`packages/kernel` ganha `InMemoryEventBus implements IEventBus` — pub/sub síncrono, em processo único, sem nenhuma dependência de infraestrutura. `services/event-bus`'s `RedisEventBus` passa a **compor** uma instância de `InMemoryEventBus` para toda a entrega local, e usa `RedisPublisher` apenas para o publish no canal Redis — nunca reimplementa o mapa de handlers.

`IEventBus` continua sendo a única interface que qualquer Module ou consumidor deve depender — nenhuma mudança de nome, porque já segue a convenção de prefixo `I` de todo o Kernel API (ADR-0052: `IContainer`, `ILogger`, `IEventBus`, `IModule`, `IConfiguration`, `IHealth`). `EventBusModule` continua bindando `IEventBus::class` no Container; nenhum consumidor jamais recebe `RedisEventBus` ou `Predis\Client` diretamente.

## Consequências

- `InMemoryEventBus` é reutilizável — como implementação padrão em testes, como base de composição de qualquer implementação futura com alcance real (`RabbitMQEventBus`, `KafkaEventBus`), e como opção de produção legítima para quem não precisa de entrega entre processos.
- `RedisEventBus` fica menor e mais honesto: sua única responsabilidade própria é o publish no Redis: a entrega local não é mais um detalhe escondido dentro dela.
- A limitação já registrada no Decision Log da Release 2 (nenhum listener Redis real, cross-processo, via `pubSubLoop`) continua existindo — esta mudança reorganiza onde a entrega local vive, não implementa consumo cross-processo. Isso fica para quando houver um consumidor de verdade.
- Nenhum consumidor de `IEventBus` precisa mudar — a interface pública não mudou, só a implementação interna de `RedisEventBus`.
