# ADR-0076: Eventos de domínio ganham metadata padrão — direção aprovada, implementação adiada

- **Status**: Proposto — direção aprovada pelo Product Owner, sem mudança de código nesta Release
- **Data**: 2026-08-04

## Contexto

Os dez eventos do Identity Engine ([DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md)) hoje carregam só o payload de negócio (`identityId`, `userId`, etc.) — os campos de correlação (identificador de origem, timestamp, Engine publicador) são mencionados em [EVENT_MODEL.md — Regras](../../EVENT_MODEL.md#regras) como obrigatórios, mas nenhuma estrutura formal os agrupa. O Product Owner pediu metadata explícita e nomeada — `id`, `timestamp`, `correlationId`, `causationId`, `actor`, `workspace` — junto do `payload`, para viabilizar Audit de verdade quando o Audit Engine (Release 11) existir.

## Decisão

Direção aprovada, **não implementada nesta Release**: quando o mecanismo real de publicação de evento (hoje `Application/UseCase/*` chamando `IEventBus::publish(string $name, array $payload)`) for revisado — provavelmente junto do Audit Engine (Release 11) ou antes, se o Memory Engine (Release 4) precisar consumir eventos de verdade — envolver todo evento numa estrutura de metadata padrão: `id` (identificador único do evento em si, distinto do agregado), `timestamp`, `correlationId` (amarra uma cadeia de eventos à mesma operação de origem), `causationId` (qual evento causou este, quando aplicável), `actor` (quem/o que disparou), `workspace` (quando aplicável), e só então `payload` com os dados de negócio específicos.

`DomainEvent::payload()` (`packages/identity-engine/src/Domain/Event/DomainEvent.php`) continua sendo só o payload de negócio — a metadata é responsabilidade de quem publica (`Application`), não do evento de domínio em si, mantendo `Domain/` livre de conhecer `correlationId`/`actor` (conceitos de infraestrutura de request, não de regra de negócio).

## Consequências

- Fica registrado como pré-requisito de fato para o Audit Engine (Release 11) ter uma trilha de auditoria completa — sem `causationId`/`correlationId`, reconstruir "o que causou o quê" fica impossível depois que muitos Engines publicarem eventos.
- Nenhum código muda agora — os dez eventos do Identity Engine continuam exatamente como estão.
- `IEventBus::publish(string $event, array $payload)` (`packages/kernel/src/Contract/IEventBus.php`) pode precisar de uma assinatura nova (`publish(DomainEvent $event, Metadata $metadata)` ou equivalente) quando isso for implementado — decisão de interface adiada para quando o trabalho real começar, não antecipada aqui.
