# ADR-0065: `Session` autentica uma `Identity`, não diretamente um `User`

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Um `User` só existe uma vez por Tenant, mas a mesma pessoa pode precisar operar em mais de um contexto ao mesmo tempo — o Product Owner deu o exemplo de Workspace Comercial e Workspace Financeiro simultâneos. Se `Session` referenciasse `UserId` diretamente, o modelo natural seria "uma sessão ativa por User", o que entra em conflito direto com esse cenário.

## Decisão

`Session` (`packages/identity-engine/src/Domain/Session.php`) referencia `IdentityId`, não `UserId` — `Session::start(IdentityId $identityId, ...)`. Como `Identity::authenticate()` pode ser chamado múltiplas vezes para a mesma `Identity`, produzindo `Sessions` distintas (`SessionId` diferente a cada chamada), a mesma pessoa pode ter tantas Sessions concorrentes quanto precisar, cada uma potencialmente associada a um Workspace diferente (ver [ADR-0066](0066-context-imutavel.md)).

## Consequências

- Múltiplas Sessions simultâneas por pessoa são suportadas nativamente, sem precisar de nenhum conceito adicional — é só chamar `authenticate()` de novo.
- `Identity::endSession()` e a verificação de pertencimento (`assertSessionBelongsToIdentity`) usam `IdentityId`, garantindo que uma Session de uma Identity nunca seja aceita para operações de outra — coberto em `IdentityTest::test_a_session_from_a_different_identity_is_rejected`.
- Não há, ainda, nenhum limite de quantas Sessions concorrentes uma Identity pode ter — fica para a Release 3B decidir se algum limite é necessário (ex: por motivo de segurança), já que isso envolveria uma política de infraestrutura, não uma regra de domínio.
