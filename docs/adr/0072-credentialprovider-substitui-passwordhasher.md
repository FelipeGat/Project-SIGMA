# ADR-0072: `CredentialProvider` substitui `PasswordHasher`

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

`PasswordHasher` (`packages/identity-engine/src/Application/PasswordHasher.php`), introduzido na Release 3B, presume senha como o único mecanismo de credencial — `hash(string $plain): string` e `verify(string $plain, string $hash): bool`. O Product Owner apontou que o domínio de "provar quem você é" não muda quando o mecanismo muda: senha hoje, Passkey ou OAuth amanhã, biometria depois — só a implementação por trás da interface muda.

## Decisão

`PasswordHasher` é renomeado para `CredentialProvider`, mesma assinatura (`hash`/`verify`) — **renomeação pura, nenhuma mudança de comportamento**. A implementação de produção (`Argon2idPasswordHasher`) é renomeada para `Argon2idCredentialProvider`, continua usando `password_hash`/`password_verify` com Argon2id — nada muda no algoritmo. Quando um segundo mecanismo de credencial existir de fato (Passkey, OAuth, API Key), a interface `CredentialProvider` pode precisar de métodos adicionais ou de uma composição de estratégias — isso é decidido quando esse mecanismo for implementado, não antecipado agora.

## Consequências

- Nenhum comportamento muda — os 135 testes do monorepo continuam cobrindo exatamente o mesmo caminho, só com o nome novo.
- O nome da interface para de presumir "senha" como a única forma de credencial, sem comprometer a Release 3.5 a implementar SSO/OAuth/Passkey agora — só renomeia o que já existe.
- `Authenticate`, `RegisterIdentity`, `IdentityEngineModule` e os testes que dependiam de `PasswordHasher` são atualizados para `CredentialProvider` no mesmo commit — nenhuma referência ao nome antigo sobrevive no código.
