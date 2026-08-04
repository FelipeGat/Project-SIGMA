# ADR-0073: Identity existe independentemente do método de autenticação

- **Status**: Aceito — confirma comportamento já implementado, sem mudança de código
- **Data**: 2026-08-04

## Contexto

O Product Owner pediu para oficializar uma regra: "Identity nunca pode depender de autenticação... Login é apenas um meio de obter uma Identity. A Identity continua existindo mesmo sem autenticação." O receio é code/design onde `Identity` só existiria como subproduto de um login bem-sucedido, dificultando SSO, OAuth, API Keys, Service Accounts, tokens, CLI — mecanismos onde "autenticar" não é necessariamente "logar com senha".

## Decisão

Confirma-se que `Identity::create()` (`packages/identity-engine/src/Domain/Identity.php`) já constrói e persiste uma `Identity` sem que nenhuma autenticação aconteça — `Identity::authenticate()` é um método separado, chamado depois, que exige a `Identity` já existir e estar ativa. `RegisterIdentity` (Application) chama `create()`+`activate()` sem nenhum `CredentialProvider` envolvido na existência da Identity em si — o `CredentialProvider` só entra para permitir a etapa seguinte, `authenticate()`, não para a Identity existir. Nenhuma mudança de código é necessária — este comportamento já é o que a Release 3A/3B implementou; esta ADR só o declara como princípio permanente, para que nenhuma mudança futura o quebre sem passar por uma ADR nova.

## Consequências

- SSO, OAuth, API Keys, Service Accounts, CLI — qualquer mecanismo futuro de "provar quem é" — só precisa produzir uma `Session` a partir de uma `Identity` já existente, nunca criar a `Identity` como efeito colateral do próprio mecanismo de autenticação.
- Uma `Identity` pode ser provisionada administrativamente (ex: por um admin, ou por sincronização de outro sistema) antes de qualquer credencial existir — já suportado hoje, sem mudança.
- Nenhum teste muda — o comportamento já estava correto; esta ADR é puramente declarativa.
