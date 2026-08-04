# ADR-0052: Kernel API — apenas interfaces, nunca classes concretas

- **Status**: Aceito — refina [KERNEL.md](../../KERNEL.md) e [BOOTSTRAP.md](../../BOOTSTRAP.md)
- **Data**: 2026-08-04

## Contexto

O contrato `Module` já especificado em [BOOTSTRAP.md](../../BOOTSTRAP.md) não deixava explícito através de que tipo o Kernel expõe suas próprias capacidades (logging, event bus, configuração...) a um Module. Se um Module importar uma classe concreta do Kernel diretamente, qualquer mudança de implementação interna do Kernel se torna uma mudança que potencialmente quebra todo Module existente.

## Decisão

O Kernel expõe exclusivamente interfaces — `ILogger`, `IEventBus`, `IModule`, `IConfiguration`, `IHealth`, `IContainer` — nunca uma classe concreta. Um Module depende dessas interfaces via injeção de dependência (ver [ADR-0044](0044-configuration-provider.md)); a implementação concreta por trás de cada interface pode mudar sem que nenhum Module precise mudar.

## Consequências

- O núcleo permanece estável mesmo quando sua implementação interna evolui — trocar o backend de logging, por exemplo, nunca é uma mudança visível a um Module.
- Reforça, num nível mais concreto de código, o mesmo princípio já presente em [ADR-0040](0040-bootstrap-nao-conhece-engines.md) (Kernel genérico) e no princípio de Clean Architecture de [ARCHITECTURE.md §1](../../docs/architecture/ARCHITECTURE.md).
- Exige disciplina de nomenclatura: toda superfície pública do Kernel é uma interface nomeada com prefixo `I`, implementada por uma classe concreta que nunca é importada fora do próprio pacote `kernel`.
