# Release 3B — Identity Infrastructure

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 1 — aguardando aprovação do Product Owner.** Segunda metade da Release 3 — Identity Engine ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)), escrita agora que a [Release 3A — Identity Domain](0003a-identity-domain.md) está implementada e validada ([Decision Log](0003a-identity-domain-decision-log.md), [Validation Report](0003a-identity-domain-validation-report.md)). Nenhuma linha de código desta sub-Release é escrita antes de aprovação explícita — mesma disciplina de 3A.

Ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md) (quatro camadas DDD), [ADR-0064](../adr/0064-identity-como-agregado-raiz.md)–[ADR-0068](../adr/0068-autonomy-por-capability.md) (as cinco decisões de direção já implementadas em `Domain/`), [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md), `contracts/Identity.contract.yaml`.

## Objetivo

Dar alcance real ao domínio já modelado e testado em 3A: persistir as entidades em MariaDB, publicar de fato os eventos de domínio no Event Bus, e expor autenticação/resolução de Identity via HTTP — o primeiro `IModule` de domínio real do SIGMA subindo pelo Bootstrap (Release 2), e o primeiro banco de dados do projeto.

## Escopo

**Existe:**
- `packages/identity-engine/src/Application/` — casos de uso que orquestram `Domain/`, sem conhecer MariaDB/HTTP diretamente ([ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)): `RegisterIdentity` (cria `User`+`Identity`, ativa), `Authenticate` (login — verifica credencial, chama `Identity::authenticate()`), `SelectWorkspace`, `ResolveContext`, `Logout`, `AssignRole`, `RevokeRole`, `GrantPermission`, `RevokePermission`. Cada caso de uso, ao final, retira os eventos pendentes do aggregate (`pullDomainEvents()`) e os publica via `IEventBus` — é aqui, não em `Domain/`, que a regra do [ADR-0062](../adr/0062-identity-nunca-conhece-outro-engine.md) ("Domain nunca publica sozinho") se cumpre.
- Interfaces de repositório declaradas em `Application/` (`TenantRepository`, `CompanyRepository`, `WorkspaceRepository`, `UserRepository`, `TeamRepository`, `RoleRepository`, `RoleAssignmentRepository`, `SessionRepository`) e uma `PasswordHasher` — implementadas por `Infrastructure/`, nunca o contrário.
- `packages/identity-engine/src/Infrastructure/` — implementação das interfaces acima sobre PDO/MariaDB (ver "Arquitetura" abaixo pela escolha de camada de acesso a dados), migrations para as doze tabelas de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) (`tenants`, `companies`, `workspaces`, `users`, `teams`, `team_memberships`, `workspace_memberships`, `roles`, `permissions`, `role_permissions`, `role_assignments`, `sessions`), `PasswordHasher` real sobre `password_hash()`/`password_verify()` com `PASSWORD_ARGON2ID`. `tenant_id` obrigatório desde a primeira migration, exceto na própria tabela `tenants` ([ADR-0021](../adr/0021-multitenancy-desde-o-schema.md)).
- `packages/identity-engine/src/Interface/` — `IdentityEngineModule implements IModule` (`kind: Engine`, `dependsOn: ['kernel', 'event-bus']`), registrando os casos de uso e repositórios no Container.
- `services/auth` — casca HTTP mínima: `POST /auth/login`, `POST /auth/logout`, `POST /auth/workspace` (seleciona Workspace na Session vigente), `GET /auth/context` (resolve e retorna o Context), todas as respostas no [Envelope](../../SIGMA_PROTOCOL.md#1-o-envelope), consumindo `packages/identity-engine` via `IContainer` — mesmo padrão de `services/gateway` consumindo `packages/kernel`.
- `docker-compose.yml` ganha o serviço `mariadb` — e, diferente da Release 2, `docker compose up --build` é validado de fato, com evidência no Validation Report (não repetir a pendência registrada no [Validation Report da Release 2](0002-sigma-bootstrap-validation-report.md)).
- `system-manifest.yaml` ganha o Module `identity-engine`.

**Não existe ainda:** OAuth/SSO/2FA, interface Web/mobile, Capability Registry enforcement (Release 8), billing/onboarding self-service de novo Tenant, qualquer limite de Sessions concorrentes por Identity (sinalizado em [ADR-0065](../adr/0065-session-autentica-identity.md), não decidido aqui), reconciliação entre `autonomy_level_required` numérico e `autonomyCapabilities` nomeado ([ADR-0068](../adr/0068-autonomy-por-capability.md) — fica para o Skill Engine, Release 8).

**Onde vive:**
- `packages/identity-engine/src/Application/`, `Infrastructure/`, `Interface/` — as três camadas que faltavam.
- `services/auth` — novo serviço deployável.
- `docker/docker-compose.yml` atualizado com `mariadb`.

## Arquitetura

`Interface/IdentityEngineModule` só conhece as seis interfaces do Kernel API ([ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md)) — nunca `Predis\Client`/PDO diretamente. `Application/` só conhece `Domain/` e suas próprias interfaces de repositório — nunca MariaDB, nunca `IEventBus` diretamente exceto para publicar o que `Domain/` já produziu como evento. `Infrastructure/` implementa essas interfaces sobre PDO puro (decisão desta Proposal, não mais uma pergunta em aberto — ver abaixo) e sobre `IEventBus` (para o publish de fato).

**Decisão sobre camada de acesso a dados** (pergunta deixada em aberto na Proposal de 3A, resolvida agora): **PDO puro + um runner de migration mínimo próprio**, mantendo o precedente framework-agnóstico de `packages/kernel` (nenhuma dependência tipo `illuminate/database`/Doctrine DBAL). Cada repositório é uma classe pequena, uma query por método, sem query builder genérico — o volume de queries desta Release (CRUD simples sobre doze tabelas) não justifica a complexidade adicional de um ORM/DBAL completo. Revisitável numa ADR própria se um Engine futuro precisar de algo mais sofisticado (queries compostas, migrations com rollback automático).

**Filtro por Tenant**: cada método de repositório que lista/busca recebe `TenantId` explicitamente como parâmetro obrigatório (nunca opcional) — o filtro `WHERE tenant_id = ?` vive dentro do próprio repositório, não é responsabilidade de quem chama lembrar de aplicá-lo. `Application/` sempre resolve o `TenantId` a partir do contexto da chamada (ex: da Session/Identity autenticada) antes de repassar a um repositório.

**Migrations no Lifecycle**: rodam como parte do `boot()` de `IdentityEngineModule` — antes de `ready`, o schema precisa estar aplicado. Se isso revelar que o contrato `IModule` genérico não comporta bem uma etapa de migration (risco já sinalizado desde a Proposal de 3A e no Decision Log de 3A), isso vira ADR nova durante a Implementation, não um ajuste silencioso.

## Dependências

- Release 3A — Identity Domain, implementada e validada.
- Release 2 — SIGMA Bootstrap (`IModule`, `IContainer`, `IEventBus`, `IConfiguration`, System Manifest com `manifestVersion`).
- MariaDB disponível no ambiente de desenvolvimento (`docker-compose.yml` resolve isso localmente, mesmo padrão do Redis na Release 2) — **desta vez o build precisa ser validado de fato**, não apenas escrito.

## Riscos

1. **Migrations dentro do Lifecycle podem não se encaixar bem no contrato `IModule` genérico** — mitigado por tratar isso como parte central da Implementation; se não se sustentar, vira ADR nova.
2. **Superfície de autenticação real é sensível por natureza** — mitigado por usar só primitivas padrão da linguagem (`password_hash`/`password_verify` com Argon2id, `random_bytes` já usado em `Sigma\Core\Id` para tokens de Session), nunca criptografia própria; sem OAuth/SSO/2FA nesta Release (escopo deliberadamente restrito).
3. **Primeira persistência real do projeto** — schema errado é caro de corrigir depois. Mitigado por `Domain/` já validado em 3A antes de qualquer migration ser escrita — o próprio propósito da divisão 3A/3B.
4. **Docker/MariaDB nunca foi validado de fato no projeto (pendência repetida desde a Release 2)** — vira Critério de Aceite explícito e obrigatório desta Release, não mais uma pendência aceita.
5. **Escopo pode crescer organicamente** (autenticação "de verdade" convida a adicionar OAuth/2FA/recuperação de senha) — mitigado pela lista fechada de "Escopo" acima.

## Entregáveis

- `packages/identity-engine/src/Application/`, `Infrastructure/`, `Interface/` implementados.
- Migrations para as doze tabelas.
- `services/auth` com `POST /auth/login`, `POST /auth/logout`, `POST /auth/workspace`, `GET /auth/context`.
- `docker/docker-compose.yml` com `mariadb`, `docker compose up --build` validado de fato.
- `system-manifest.yaml` atualizado com o Module `identity-engine`.
- **Decision Log** (`docs/releases/0003b-identity-infrastructure-decision-log.md`).
- **Validation Report** (`docs/releases/0003b-identity-infrastructure-validation-report.md`), desta vez com as seções "Docker" e "HTTP" preenchidas de fato, não "não aplicável" como em 3A.

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- `Application/`: testado com implementações em memória das interfaces de repositório (test doubles) — rápido, sem depender de banco, mesmo espírito dos testes de `Domain/` em 3A.
- `Infrastructure/`: testado contra uma MariaDB real (via `docker-compose`) — round-trip de cada repositório (salvar, buscar, listar, respeitando `tenant_id`).
- Migrations aplicam limpo em um banco vazio; rodar duas vezes não falha (idempotência básica).

### 2. Architecture Validation
- `Application/` não importa nenhuma classe de `Infrastructure/` (só as interfaces que ela mesma declara).
- `Interface/IdentityEngineModule` não importa PDO/MariaDB diretamente.
- `services/auth` não importa nenhuma classe concreta do Kernel fora das seis interfaces.

### 3. Scenario Validation
- `docker-compose up --build` real, com `mariadb` — subida completa validada (evidência no Validation Report).
- Login com credencial válida → Session emitida → `POST /auth/workspace` → `GET /auth/context` retorna Permissions/Autonomy corretas — via HTTP real (`curl`), não só teste automatizado.
- Login com credencial inválida → rejeitado explicitamente, sem Session emitida.
- Session expirada → `GET /auth/context` rejeita explicitamente.
- Reiniciar o container do gateway/auth não perde dados já persistidos (prova de que a persistência é real, não em memória).

## Critérios de Aceite

- Todas as doze tabelas existem, com `tenant_id` obrigatório onde aplicável, migrations aplicadas via `docker-compose up --build` real.
- `IdentityEngineModule` sobe via Bootstrap sem exigir mudança no contrato `IModule` — ou, se exigir, a mudança é uma ADR nova.
- Um Context correto é resolvido via HTTP real, refletindo Tenant/Company/Workspace/Permissions/Autonomy.
- Eventos de domínio de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) publicados de fato no Event Bus a cada operação correspondente.
- Os três níveis de validação executados e documentados no Validation Report — Docker e HTTP preenchidos de verdade, não "não aplicável".
