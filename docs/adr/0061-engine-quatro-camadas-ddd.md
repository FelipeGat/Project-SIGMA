# ADR-0061: Todo Engine segue quatro camadas DDD — Domain, Application, Infrastructure, Interface

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até a Release 2, "Engine"/"Module" era tratado como uma unidade só (`register()`/`boot()`/`describe()` de um `IModule`), adequado para infraestrutura pura sem regra de negócio própria. A Release 3 é o primeiro Engine a modelar domínio de verdade (ver [ADR-0060](0060-release-dividida-em-sub-releases.md)) — sem uma separação interna explícita, regra de negócio, orquestração de caso de uso, e detalhe de persistência tendem a se misturar dentro do mesmo pacote, exatamente o tipo de acoplamento que [KERNEL.md](../../KERNEL.md) já evita na fronteira entre Kernel e Module, mas que nada garantia ainda **dentro** de um Engine.

## Decisão

Todo Engine que modela domínio (a partir da Release 3) organiza seu pacote em quatro camadas, nesta ordem de dependência (uma camada só depende das que vêm antes dela nesta lista, nunca das que vêm depois):

```
packages/<engine>/
├── Domain/          — Value Objects, Entities, Aggregates, eventos de domínio, regras de negócio. Sem dependência de framework, banco ou HTTP.
├── Application/      — Casos de uso/orquestração (ex: "autenticar User", "atribuir Role"). Depende de Domain; expõe interfaces que Infrastructure implementa.
├── Infrastructure/    — Persistência, clientes externos, implementações concretas das interfaces de Application/Domain (ex: repositórios sobre MariaDB).
└── Interface/         — Onde o Engine é alcançado de fora: `IModule` (register/boot/describe), e, quando aplicável, a casca HTTP correspondente.
```

Exemplo concreto — `packages/identity-engine/`: `Domain/` com `TenantId`, `UserId`, `Identity` (agregado), `IdentityCreated` (evento); `Application/` com o caso de uso de autenticação; `Infrastructure/` com o repositório de `User` sobre MariaDB; `Interface/` com `IdentityEngineModule implements IModule`.

## Consequências

- Regra de negócio (Domain) nunca depende de como ela é persistida ou exposta — pode ser testada isoladamente, sem banco, sem HTTP, exatamente o que torna a Release 3A (Domain) possível como uma sub-Release própria sem infraestrutura ([ADR-0060](0060-release-dividida-em-sub-releases.md)).
- Trocar a tecnologia de persistência (ex: de MariaDB para outra coisa, hipoteticamente) afeta só `Infrastructure/`, nunca `Domain/` ou `Application/`.
- Custo: mais estrutura de pastas e mais indireção (interfaces em `Application`/`Domain`, implementações em `Infrastructure`) do que colocar tudo num pacote só — aceito porque o ganho de testabilidade e isolamento supera isso a partir do primeiro Engine com regra de negócio real.
- Não se aplica retroativamente a `packages/kernel`/`services/event-bus`/`services/gateway` (Release 2) — são infraestrutura pura, sem Domain próprio; a estrutura de camadas só é exigida a partir de um Engine que modela domínio.
