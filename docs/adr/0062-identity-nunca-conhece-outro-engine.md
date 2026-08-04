# ADR-0062: Identity Engine nunca conhece outro Engine — comunicação apenas por eventos de domínio publicados

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O Identity Engine responde "quem" para todo o resto do SIGMA ([ADR-0039](0039-identity-engine.md)) — praticamente todo outro Engine (Memory, Mission, Planner, Agent, Skill, Execution, Audit) precisa, direta ou indiretamente, de algo que o Identity resolve (Tenant, Workspace, Permission, Autonomy). Essa centralidade cria um risco natural de acoplamento na direção contrária: o Identity Engine sendo tentado a importar ou conhecer estruturas do Memory Engine, do Mission Engine, etc., para "facilitar" alguma integração — o que o transformaria num hub acoplado a tudo, o oposto do que a arquitetura orientada a eventos já estabelece para o SIGMA como um todo ([ADR-0008](0008-arquitetura-orientada-a-eventos.md), [ADR-0018](0018-tudo-e-evento.md), [EVENT_MODEL.md](../../EVENT_MODEL.md)).

## Decisão

O Identity Engine **nunca importa, referencia ou conhece** nenhum outro Engine (Memory, Mission, Planner, Agent, Skill, Execution, Audit) — nem no sentido de dependência de código (`use` de uma classe de outro pacote), nem no sentido de saber que esses Engines existem. Toda comunicação do Identity Engine para o resto do sistema acontece exclusivamente publicando eventos de domínio no Event Bus (`IEventBus`, ver [ADR-0057](0057-eventbus-composicao-inmemory.md)) — quem quiser reagir, escuta; o Identity nunca sabe quem está ouvindo, nem se alguém está.

Catálogo inicial de eventos que o Identity Engine publica: ver [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md).

Esta regra é mais estrita do que a arquitetura orientada a eventos já exige de qualquer Engine em geral — para o Identity especificamente, dada sua centralidade, nem sequer uma dependência de leitura ou um import de tipo é permitido, só o evento publicado.

## Consequências

- Nenhum outro Engine, hoje ou no futuro, quebra o Identity Engine mudando sua própria estrutura interna — o único contrato entre eles é o formato dos eventos publicados, versionado como qualquer outro evento ([EVENT_MODEL.md](../../EVENT_MODEL.md), regra 3).
- O Identity Engine pode ser desenvolvido, testado e até implantado de forma completamente isolada dos demais — reforça a divisão Domain-first da Release 3A ([ADR-0060](0060-release-dividida-em-sub-releases.md)).
- Quem consome os eventos de Identity (tipicamente Memory Engine, para o Digital Twin de User/Company, e Audit Engine, para trilha de conformidade) decide por conta própria como e quando reagir — o Identity nunca invoca nada diretamente em nenhum consumidor.
- Custo: qualquer necessidade real de consulta síncrona de outro Engine ao Identity (ex: "qual é o Autonomy Level efetivo agora") não pode ser resolvida por um import direto — precisa passar pelo Context já resolvido e disponibilizado pelo Kernel ([IDENTITY_MODEL.md](../../IDENTITY_MODEL.md#context), [KERNEL.md](../../KERNEL.md)), nunca por uma chamada direta ao Identity Engine.
