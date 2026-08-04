# ADR-0074: `Session` é um Aggregate autônomo, não uma sub-entidade de `Identity`

- **Status**: Aceito — confirma design já implementado, sem mudança de código
- **Data**: 2026-08-04

## Contexto

O Product Owner pediu para confirmar que `Session` é tratada como Aggregate próprio, com ciclo de vida próprio, não uma estrutura aninhada dentro de `Identity`.

## Decisão

Confirma-se: `Session` (`packages/identity-engine/src/Domain/Session.php`) já é uma classe própria desde a Release 3A, com construtor privado, factories próprias (`start()`, `reconstitute()`) e seu próprio ciclo de vida (`issuedAt`/`expiresAt`/`workspaceId`/expiração). `Identity` não guarda uma lista de Sessions ativas nem as gerencia diretamente — `Identity::authenticate()` produz uma `Session` e a devolve; quem persiste e consulta Sessions depois é `SessionRepository` (Application/Infrastructure), não `Identity`. Nenhuma mudança de código é necessária.

## Consequências

- `Session` pode evoluir seu próprio ciclo de vida (revogação em massa, limite de Sessions concorrentes — sinalizado em [ADR-0065](0065-session-autentica-identity.md), não decidido) sem precisar mexer no aggregate `Identity`.
- Reforça a leitura já estabelecida em [ADR-0065](0065-session-autentica-identity.md): `Session` referencia `IdentityId`, nunca o contrário.
