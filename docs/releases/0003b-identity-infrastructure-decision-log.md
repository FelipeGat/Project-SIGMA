# Release 3B — Identity Infrastructure — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0003b-identity-infrastructure.md](0003b-identity-infrastructure.md) (revisão 1). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

- `packages/identity-engine/src/Application/` — 9 interfaces de repositório, `PasswordHasher`, `CredentialRepository`, e 9 casos de uso (`RegisterIdentity`, `Authenticate`, `SelectWorkspace`, `ResolveContext`, `Logout`, `AssignRole`, `RevokeRole`, `GrantPermission`, `RevokePermission`) — cada um publica os eventos pulados do aggregate via `IEventBus` ao final.
- `packages/identity-engine/src/Infrastructure/` — `MigrationRunner` + `CreateSchema` (as doze tabelas), 9 repositórios `Pdo*` sobre PDO puro (sem Doctrine DBAL, conforme decidido na Proposal), `Argon2idPasswordHasher`.
- `packages/identity-engine/src/Interfaces/` — `IdentityEngineModule implements IModule`.
- `services/auth` — `Bootstrap`, `AuthEndpoints`, front controller com `POST /auth/login`, `POST /auth/logout`, `POST /auth/workspace`, `GET /auth/context`.
- `docker/docker-compose.yml` com `mariadb` + `docker/auth.Dockerfile`, **`docker compose up --build` executado de fato** (ver Validation Report).
- `system-manifest.yaml` com o Module `identity-engine`.
- **72 testes em `packages/identity-engine`** (62 de domínio/aplicação em memória + 10 de Infrastructure contra MariaDB real) e **5 em `services/auth`** (contra MariaDB real).

## Decisões locais e o porquê

### `Interfaces/`, no plural — não `Interface/`

`interface` é palavra reservada do PHP; `namespace Sigma\IdentityEngine\Interface;` é erro de sintaxe. Descoberto ao criar o primeiro arquivo desta camada. O nome conceitual da camada continua "Interface" (singular) na documentação de arquitetura ([ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md), atualizada); só o namespace/pasta física é `Interfaces/`.

### `Identity`/`Session`/`RoleAssignment` ganharam `reconstitute()`

A Release 3A só validou `create()`/`assign()` — factories que sempre disparam um evento de domínio. Carregar um aggregate já existente do banco através dessas factories dispararia `IdentityCreated`/`RoleAssigned` de novo a cada leitura — errado. Adicionado `reconstitute()` a cada um dos três (`Identity`, `Session`, `RoleAssignment`), que reidrata o estado sem gravar nenhum evento — coberto por teste dedicado em cada caso. **Amendment sobre código já "implementado e validado" da Release 3A** — mudança aditiva, nenhum teste ou comportamento anterior quebrou; sinalizada aqui explicitamente por transparência, não escondida.

### `Team` ganhou `members(): array`

`PdoTeamRepository` precisava persistir a lista de membros; `Team` só expunha `hasMember()`. Cogitou-se `\ReflectionProperty` para contornar isso sem mudar `Domain/` — descartado por ser mais frágil e menos legível que simplesmente adicionar um getter. Mesma categoria de amendment que `reconstitute()`: aditivo, testado, sem quebrar nada da 3A.

### Schema: doze tabelas, mas não as mesmas doze da Proposal original

A Proposal de 3A/3B listava `tenants, companies, workspaces, users, teams, team_memberships, workspace_memberships, roles, permissions, role_permissions, role_assignments, sessions` — presumia uma tabela `permissions` (catálogo) e não previa uma tabela `identities`. Na implementação: `Permission` é identificada por sua chave string natural (`mission.create`), não por um `PermissionId` substituto — não precisa de tabela catálogo própria, só `role_permissions (role_id, permission_key)`. Em compensação, `Identity` (ADR-0064, agregado raiz com estado próprio — `active`) precisa de uma tabela própria, distinta de `users`. Contagem final: doze tabelas também, mas `identities` no lugar de `permissions`. `PermissionId` (Value Object já criado na Release 3A, conforme a lista original do Product Owner) fica sem uso na Infrastructure — registrado aqui como gap conhecido, não escondido, não removido (pode ganhar uso se `Permission` precisar de um catálogo com metadados no futuro).

### `password_hash` mora na tabela `users`, não numa tabela própria

`CredentialRepository` é uma interface separada de `UserRepository` (Application), mas a persistência real reutiliza a mesma tabela `users` — coluna `password_hash`, nula até `RegisterIdentity` chamar `CredentialRepository::setPasswordHash()`. Criar uma tabela `credentials` à parte só para uma coluna seria over-engineering para o escopo desta Release.

### Um Module que lança `\PDOException` crua é um bug — corrigido durante a Implementation

Ao escrever `services/auth/tests/BootstrapTest.php` (que precisa pular graciosamente quando MariaDB está inalcançável, mesmo padrão dos testes de Infrastructure), descobri que `IdentityEngineModule::register()` deixava `\PDOException` vazar sem transformar em `SigmaException` — inconsistente com todo o resto do Kernel API, que só lança `SigmaException` (`ConfigurationProvider`, `LifecycleManager`, etc.). Corrigido: `register()` agora captura `\PDOException` e relança como `SigmaException` (`identity_engine.database_unreachable`), com a exceção original preservada como `$previous`. **Achado real de um teste sendo escrito, não de uma revisão manual** — exatamente o tipo de coisa que a disciplina de testes existe para pegar.

### `AuthEndpoints::guarded()` não capturava `\Throwable`, só `SigmaException`

Descoberto ao validar `/auth/login` via HTTP real com Redis genuinely inalcançável (antes do `docker compose up` funcionar) — a exceção de conexão do Predis não é uma `SigmaException`, e sem um catch genérico, a resposta HTTP deixava de ser um Envelope JSON válido (contrato quebrado). Corrigido: `guarded()` agora também captura `\Throwable`, registra a mensagem real via `error_log()` (nunca no corpo da resposta) e retorna `500` com `auth.internal_error` — genérico, sem vazar detalhe de infraestrutura ao cliente. Validado depois: o mesmo cenário (login com Redis fora do ar) agora produz um Envelope de erro limpo, não uma resposta quebrada.

### `system-manifest.yaml`: `identity-engine` como `optional: true`

Descoberto ao tentar subir `services/gateway` com o Manifest já atualizado para incluir `identity-engine`: `gateway` só registra `kernel`+`event-bus`, nunca `identity-engine` — com `optional: false`, isso quebrava o boot do `gateway` (`lifecycle.required_module_missing`), mesmo `gateway` nunca tendo precisado desse Module. `optional: true` aqui não significa "este Module é dispensável no geral" — significa "nem todo processo que lê este Manifest compartilhado precisa registrá-lo" (ver comentário no próprio `system-manifest.yaml`). `services/auth` sempre o registra, então do ponto de vista de `auth` ele é, na prática, sempre presente e sempre `ready`. **Gap de design do System Manifest exposto pela primeira vez** — até a Release 2, um único processo (`gateway`) lia o Manifest inteiro; a partir de dois processos (`gateway` e `auth`) lendo o mesmo arquivo mas registrando subconjuntos diferentes, essa hipótese implícita quebrou. Não gerou ADR nova porque `optional` já cobria exatamente esse caso — só não tinha sido usado com esse propósito antes.

### `docker compose up --build` validado de fato — pela primeira vez no projeto

Docker Desktop, indisponível nas sessões da Release 2 e no início desta, ficou disponível no meio desta Release. Rodado de verdade: os quatro serviços (`redis`, `mariadb`, `gateway`, `auth`) subiram, `gateway` respondeu `/health/ready` 200, e o fluxo completo (`login` → `select workspace` → `context` → `logout`) funcionou via HTTP real contra o Envelope, incluindo publicação real de evento no Redis (antes só validado via `InMemoryEventBus` local). Portas do host remapeadas (`18080`/`18081`/`13306`/`16379`) para não colidir com outro projeto Docker (`gestor_nginx`/`gestor_phpmyadmin`) já rodando na máquina — cuidado registrado aqui porque outra sessão futura pode se deparar com o mesmo conflito.

### MariaDB local (XAMPP) usada como validação intermediária de Infrastructure

Antes do Docker ficar disponível nesta sessão, os 10 testes de `Infrastructure/Pdo` e os 5 de `services/auth` foram validados contra o `mysqld` do XAMPP local (parado antes e depois da sessão, bancos de teste descartados ao final) — mais forte que mock, mais fraco que Docker: prova que o SQL/PDO real funciona, mas não prova o empacotamento em container. Registrado para o caso de uma sessão futura sem Docker disponível de novo precisar do mesmo caminho.

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 135 testes no monorepo (8+36+6+8+72+5), todos passando; os 10+5 que dependem de MariaDB rodaram de fato contra ela (local e depois via Docker), não foram só escritos e deixados sem execução.
2. **Architecture Validation**: `Application/` não importa nada de `Infrastructure/`; `Interfaces/IdentityEngineModule` só conhece as seis interfaces do Kernel API; `services/auth` idem.
3. **Scenario Validation**: ver [Validation Report](0003b-identity-infrastructure-validation-report.md) — `docker compose up --build` real, fluxo HTTP completo real, evento publicado de fato no Redis.

## Impacto para as próximas Releases

- O padrão "Module lança só `SigmaException`, nunca exceção de infraestrutura crua" e "endpoint HTTP sempre captura `\Throwable`, nunca só a exceção de domínio" deveriam virar checklist explícito para todo Engine futuro com infraestrutura real — hoje só documentado aqui, não uma ADR à parte (avaliar se vale a pena promover quando o próximo Engine com infraestrutura chegar, provavelmente Memory Engine).
- O gap do System Manifest compartilhado por múltiplos processos (`optional` como "nem todo processo registra") deve ser levado em conta explicitamente ao desenhar `services/auth`, `services/scheduler` etc. futuros.
