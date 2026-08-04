# ADR-0079: `UserTwin` é populado desde a Release 4 — os demais Digital Twins esperam a Release 8

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[DIGITAL_TWIN.md](../../DIGITAL_TWIN.md) previa que o primeiro Digital Twin real (`ClientTwin`) só faria sentido "junto com a Release 5 — Mission Engine ou a Release 8 — Skill Engine, quando a primeira Capability de leitura real (`GestorSkill.FindClient`) existir". [ROADMAP.md](../../ROADMAP.md), por outro lado, descreve a Release 4 como entregando "custódia dos primeiros Digital Twins" — uma tensão entre os dois documentos que precisava de resolução antes do [MEMORY_MODEL.md](../../MEMORY_MODEL.md) ser escrito. Ao mesmo tempo, o Identity Engine (Release 3) já publica eventos reais (`identity.created`, `workspace.selected`) com "Memory Engine" documentado como consumidor esperado desde a Release 3.5 ([EVENT_CATALOG.md](../../EVENT_CATALOG.md)) — uma fonte de dado real já disponível, sem depender de nenhuma Capability externa.

## Decisão

A Release 4 entrega o mecanismo genérico de `DigitalTwin` para os quatro `subjectType` (`Client`/`Project`/`Company`/`User`), mas **só popula `UserTwin` de fato**, a partir dos eventos que o Identity Engine já publica hoje. `ClientTwin`/`ProjectTwin`/`CompanyTwin` ficam com schema pronto, mecanismo de sincronização testado, mas sem nenhuma instância real até a Release 8 (`GestorSkill.FindClient`, a primeira Capability de leitura real do SIGMA sobre um sistema externo).

## Consequências

- "Custódia dos primeiros Digital Twins" (ROADMAP.md) e "primeiro Twin real só na Release 8" (DIGITAL_TWIN.md) deixam de ser contraditórios — ambos verdadeiros, para `subjectType` diferentes.
- O mecanismo de sincronização por Semantic Event é validado com dado real (eventos do Identity Engine) antes da Release 8 precisar dele para `Client`/`Project`/`Company` — reduz risco de descobrir um problema de design só quando `GestorSkill` já estiver em jogo.
- `UserTwin` nunca carrega Role/Permission/Autonomy — essa fronteira, já fixada em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), continua valendo: autorização é sempre resolvida em tempo real pelo Identity Engine, nunca lida de um Twin.
