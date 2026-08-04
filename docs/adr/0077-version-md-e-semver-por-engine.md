# ADR-0077: `VERSION.md` e Semantic Versioning por Engine

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O projeto SIGMA tem uma versão própria (`system-manifest.yaml`, campo `version`), e cada pacote Composer já declara uma versão (`packages/identity-engine/composer.json`: `"version": "1.0.0"`) — mas nada documenta explicitamente o que essa versão significa, nem existe uma política de quando ela deve subir. O Product Owner pediu que isso seja formalizado por Engine, não só por projeto: "Identity 1.0.0, Memory 0.0.0, Mission 0.0.0" — cada Engine amadurece e versiona no seu próprio ritmo.

## Decisão

Todo Engine com código real ganha um `VERSION.md` na raiz do seu pacote (`packages/<engine>/VERSION.md`), documentando: a versão atual, o que está incluído nela (por camada — Domain/Application/Infrastructure/Interfaces), os eventos publicados naquela versão (link para [EVENT_CATALOG.md](../../EVENT_CATALOG.md)), os Contracts daquela versão, o histórico de Breaking Changes (vazio até a primeira mudança incompatível), e a política de quando PATCH/MINOR/MAJOR sobem. Primeiro exemplo: [packages/identity-engine/VERSION.md](../../packages/identity-engine/VERSION.md).

Engines sem código ainda (Memory, Mission, etc.) não ganham `VERSION.md` agora — nasce junto do primeiro código real de cada um, mesmo princípio já seguido por `IDENTITY_MODEL.md`/`DOMAIN_EVENTS.md`: documentar o que existe, nunca o hipotético.

## Consequências

- Uma mudança incompatível em um Engine específico (ex: Identity 2.0.0) fica claramente separada de mudanças em outro Engine (Memory continua 0.x.0) — nenhum "version bump" de projeto inteiro por causa de um Engine só.
- `composer.json` de cada pacote continua sendo a fonte de verdade técnica da versão (o que o Composer resolve); `VERSION.md` é a fonte de verdade humana (o que essa versão significa, por quê).
- Mais um artefato a manter por Engine — aceito porque o custo de descobrir depois "o que mudou entre a versão X e Y deste Engine" sem esse histórico seria maior, especialmente quando plugins/consumidores externos existirem (Release 8 em diante).
