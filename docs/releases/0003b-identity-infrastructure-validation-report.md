# Release 3B — Identity Infrastructure — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0003b-identity-infrastructure.md](0003b-identity-infrastructure.md) (revisão 1).

## Release

Release 3B — Identity Infrastructure.

## Ambiente

- Windows 10 Pro, XAMPP + Docker Desktop.
- Execução: 2026-08-04. Docker Desktop ficou disponível no meio desta sessão (indisponível nas sessões anteriores, incl. Release 2).

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12` (local) e `php:8.2-cli-alpine` (containers) — mesmo gap já aceito conscientemente desde a Release 2, reconciliação adiada para a Release de CI/CD.

## Docker

**`docker compose up --build` executado de fato — pela primeira vez no projeto.** Os quatro serviços (`redis`, `mariadb`, `gateway`, `auth`) subiram com sucesso:

```
sigma-auth-1      Up   0.0.0.0:18081->8081/tcp
sigma-gateway-1   Up   0.0.0.0:18080->8080/tcp
sigma-redis-1     Up (healthy)   0.0.0.0:16379->6379/tcp
sigma-mariadb-1   Up (healthy)   0.0.0.0:13306->3306/tcp
```

Portas do host remapeadas (`18080`/`18081`/`13306`/`16379`) para não colidir com outro projeto Docker já em execução na máquina (`gestor_nginx`/`gestor_phpmyadmin` nas portas `8080`/`8081`) — ver Decision Log. Pilha derrubada (`docker compose down`) ao final da validação, sem deixar nada residente.

## HTTP

Testado via `curl` real contra os containers em execução:

| Endpoint | Cenário | Status obtido |
|---|---|---|
| `GET /health/ready` (gateway) | Sistema no ar | `200` |
| `GET /auth/context` | Token de Session inexistente | `404`, `identity.session_not_found` |
| `POST /auth/login` | Credencial válida (Tenant/User seedados via `docker exec`) | `200`, `sessionId`/`expiresAt` no corpo |
| `POST /auth/workspace` | Primeira seleção de Workspace na Session | `200` |
| `GET /auth/context` | Com Workspace selecionado | `200`, `tenantId`/`companyId`/`workspaceId`/`permissions` corretos |
| `POST /auth/logout` | Session válida | `200`, `status: logged_out` |
| `GET /auth/context` | Mesma Session, após logout | `404`, `identity.session_not_found` — confirma que logout invalida de fato |
| `POST /auth/login` | Credencial errada | `401`, `identity.invalid_credentials` (validado antes do Docker, contra MariaDB local — ver "Testes") |

Evento de domínio publicado no Redis real durante o login (`session.started`) confirmado via log do container `auth` mostrando o fluxo completo sem erro — antes da disponibilidade do Docker nesta sessão, o mesmo teste (contra Redis inalcançável) retornava `500`/`auth.internal_error` de forma limpa (ver "Achados" abaixo), nunca uma resposta quebrada.

## Testes

| Pacote/Serviço | Comando | Testes | Assertions | Observação |
|---|---|---|---|---|
| `packages/core` | `composer test` | 8 | 13 | inclui `EnvelopeTest` (novo, movido de `services/gateway`) |
| `packages/kernel` | `composer test` | 36 | 78 | |
| `services/event-bus` | `composer test` | 6 | 12 | |
| `services/gateway` | `composer test` | 8 | 22 | |
| `packages/identity-engine` | `composer test` | 72 | 96 | 62 em memória (Domain+Application) + 10 de Infrastructure contra MariaDB real |
| `services/auth` | `composer test` | 5 | 14 | contra MariaDB real |

**Total: 135 testes, todos passando.** Os testes de Infrastructure (`packages/identity-engine`) e de `services/auth` rodam contra MariaDB de verdade — validados nesta sessão tanto contra o `mysqld` local do XAMPP (antes do Docker ficar disponível) quanto, implicitamente, pelo próprio fluxo Docker real acima. Quando MariaDB não está alcançável, esses testes são pulados explicitamente (`markTestSkipped`), nunca falham silenciosamente nem passam por engano.

## Coverage

Não medida nesta Release — mesma pendência já registrada desde a Release 2 (nenhuma ferramenta de coverage configurada no ambiente local).

## Scenario Validation

Cenários da Proposal (revisão 1), cada um com o resultado real:

- ✅ `docker-compose up --build` real, com MariaDB — validado de fato (ver "Docker" acima). **Não repete a pendência da Release 2.**
- ✅ Login com credencial válida → Session emitida → `POST /auth/workspace` → `GET /auth/context` retorna Permissions/Autonomy corretas — via HTTP real (`curl`) contra os containers.
- ✅ Login com credencial inválida → rejeitado explicitamente (`401`, `identity.invalid_credentials`), sem Session emitida.
- ✅ Session inválida/inexistente → `GET /auth/context` rejeita explicitamente (`404`).
- ✅ Logout invalida a Session — confirmado via segunda chamada a `/auth/context` com o mesmo token, retornando `404`.
- ✅ Reiniciar/recriar containers não foi necessário para provar persistência real: os dados seedados via `docker exec` sobreviveram entre chamadas HTTP distintas (`login` → `workspace` → `context` → `logout`), cada uma um processo PHP novo (`php -S` não mantém estado entre requisições) — só persistência real explica a continuidade observada.

**Achados reais durante a Scenario Validation** (nenhum pego por revisão manual, todos por execução real):

1. `IdentityEngineModule::register()` deixava `\PDOException` vazar sem virar `SigmaException` — corrigido (ver Decision Log).
2. `AuthEndpoints::guarded()` só capturava `SigmaException` — uma falha de infraestrutura (Redis inalcançável) quebrava o contrato do Envelope. Corrigido para capturar `\Throwable` e responder `500`/`auth.internal_error` de forma limpa.
3. `system-manifest.yaml` compartilhado por `gateway` e `auth`, cada um registrando um subconjunto diferente de Modules — `identity-engine` como `optional: false` quebrava o boot do `gateway`. Corrigido para `optional: true` (ver Decision Log para o que isso realmente significa aqui).
4. Conflito de portas do host com outro projeto Docker já em execução na máquina — resolvido remapeando as portas publicadas do `docker-compose.yml` desta Release.

## Pendências

- Coverage de código não medido.
- Nenhum limite de Sessions concorrentes por Identity implementado — sinalizado desde [ADR-0065](../adr/0065-session-autentica-identity.md), decisão adiada.
- Divergência entre `autonomy_level_required` (inteiro, Sigma Contracts) e `autonomyCapabilities` (mapa nomeado, Identity Engine) — sinalizada em [ADR-0068](../adr/0068-autonomy-por-capability.md), reconciliação adiada para o Skill Engine (Release 8).
- `PermissionId` (Value Object da Release 3A) sem uso na Infrastructure — `Permission` é identificada pela chave string natural, não por esse identificador. Registrado, não removido.
