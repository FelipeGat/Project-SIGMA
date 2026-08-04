# Release 3A — Identity Domain — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0003a-identity-domain.md](0003a-identity-domain.md) (revisão 3). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

- `packages/identity-engine/src/Domain/` — `Identifier` (base de todo identificador, [ADR-0063](../adr/0063-identificadores-como-value-objects.md)) e as nove classes concretas (`TenantId`, `CompanyId`, `WorkspaceId`, `UserId`, `TeamId`, `RoleId`, `PermissionId`, `SessionId`, `IdentityId`); `Tenant`, `Company`, `Workspace`, `User`, `Team` (com `TeamType`); `Permission`, `Scope` (com `ScopeType`), `SubjectType`; `Role` (permissões + autonomy capabilities, [ADR-0068](../adr/0068-autonomy-por-capability.md)); `RoleAssignment`; `Session` ([ADR-0065](../adr/0065-session-autentica-identity.md)); `Context` ([ADR-0066](../adr/0066-context-imutavel.md)); `Identity` — o agregado raiz ([ADR-0064](../adr/0064-identity-como-agregado-raiz.md)).
- `packages/identity-engine/src/Domain/Event/` — os dez eventos de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md), cada um `final`, implementando `DomainEvent` (`name()`/`payload()`).
- `RecordsDomainEvents` — trait usado por `Identity`, `Role` e `RoleAssignment` para reter e retirar eventos pendentes, sem nunca publicá-los sozinhos ([ADR-0062](../adr/0062-identity-nunca-conhece-outro-engine.md)).
- **50 testes automatizados**, cobrindo cada Value Object, entidade, e o fluxo completo de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) ponta a ponta em memória.
- Cinco ADRs de direção escritas como parte do trabalho, não depois: [0064](../adr/0064-identity-como-agregado-raiz.md)–[0068](../adr/0068-autonomy-por-capability.md).

**Nenhuma dependência de infraestrutura** — sem banco, sem Redis, sem HTTP, sem `packages/kernel`. `composer.json` só declara `sigma/core` (identificadores/exceções) como dependência de produção.

## Decisões locais e o porquê

### `Identifier` como classe base abstrata, não uma interface

Uma interface exigiria reimplementar `equals()`/`toString()`/`generate()`/`fromString()` em cada uma das nove classes de identificador — puro boilerplate repetido. `Identifier` é uma classe abstrata com construtor `private` e métodos `final`, usando *late static binding* (`new static(...)`) para que `TenantId::generate()` retorne de fato um `TenantId`, não um `Identifier` genérico. Cada subclasse concreta (`final class TenantId extends Identifier {}`) fica com uma linha só.

### `Role` e `RoleAssignment` são pequenos aggregates próprios, não sub-entidades de `Identity`

`PermissionGranted`/`PermissionRevoked`/`RoleAssigned`/`RoleRevoked` são mudanças no `Role`/`RoleAssignment` em si (ex: um admin concedendo uma Permission nova a um Role já existente) — não necessariamente uma ação de uma `Identity` específica sobre si mesma. Modelá-los como aggregates próprios, cada um com `RecordsDomainEvents`, evita forçar toda mudança de Role/atribuição a passar por um objeto `Identity` que não tem relação direta com essa operação.

### `RoleAssignment` guarda o `Role` inteiro, não só `RoleId`

Domain não tem repositório (Release 3A não persiste nada) — não há "carregar o Role pelo id" disponível. `Identity::resolveContext()` precisa das `Permissions`/`autonomyCapabilities` de cada Role aplicável para agregar o `Context` final; passar o objeto `Role` já hidratado (responsabilidade de quem monta o cenário — na 3B, a `Application`/`Infrastructure`) mantém o Domain simples e sem noção de persistência. `RoleAssigned`/`RoleRevoked` serializam só `roleId` no payload, não o Role inteiro — a diferença entre "o que o Domain precisa em memória" e "o que um evento carrega" é intencional.

### Seleção de Workspace: primeira vez muta a mesma Session, segunda vez exige Session nova

[IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) descreve "Session criada" (sem Workspace) seguido de "Workspace selecionado" como dois passos da **mesma** sequência — a primeira seleção é a continuação natural da Session recém-criada, não uma troca. `Session::withWorkspaceSelected()` permite isso uma única vez (retornando uma nova instância com o mesmo `SessionId`) e rejeita explicitamente qualquer segunda chamada — trocar de Workspace de fato exige `Identity::authenticate()` de novo, produzindo um `SessionId` novo. Este raciocínio está documentado em [ADR-0066](../adr/0066-context-imutavel.md), mas registrado aqui como a decisão de implementação que o motivou.

### União (OR) de Permissions e Autonomy Capabilities entre múltiplos Roles aplicáveis

Quando mais de um `RoleAssignment` se aplica ao mesmo escopo (ex: uma Permission concedida via atribuição direta ao User E via um Team de que participa), o `Context` final concede o que **qualquer** um dos Roles aplicáveis concede — nunca a interseção. Mais Roles só pode dar mais acesso, nunca menos; a mesma lógica já valia implicitamente para Permission em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) e foi estendida explicitamente para Autonomy Capability nesta implementação ([ADR-0068](../adr/0068-autonomy-por-capability.md)) — decisão de design não especificada literalmente pelo Product Owner, sinalizada como tal na própria ADR.

### `RoleRevoked` adicionado por simetria — já sinalizado em `DOMAIN_EVENTS.md`

Não estava na lista original do Product Owner — todo `RoleAssignment` que pode ser criado precisa poder ser desfeito. Já registrado como acréscimo transparente na rodada anterior (criação de `DOMAIN_EVENTS.md`); implementado aqui exatamente como catalogado.

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 50 testes, `composer test` — todos passando (79 assertions).
2. **Architecture Validation**: nenhuma classe em `Domain/` importa `Application/`/`Infrastructure/`/`Interface/` (nenhuma dessas pastas existe ainda), nem `packages/kernel`, nem nenhuma biblioteca de banco/HTTP — `composer.json` só declara `sigma/core`. Todo identificador em toda assinatura pública é um `Identifier` concreto, nunca `string`.
3. **Scenario Validation**: ver [Validation Report](0003a-identity-domain-validation-report.md).

## Impacto para a Release 3B

- `Application`/`Infrastructure`/`Interface` de `packages/identity-engine` vão consumir exatamente estas classes de `Domain/` — nenhuma delas precisou ser desenhada "pensando na persistência", exatamente o que a divisão 3A/3B ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)) queria provar.
- A resolução de `memberTeamIds`/`roleAssignments` aplicáveis em `Identity::resolveContext()` espera receber essas listas já filtradas por Tenant (comentário no código) — a 3B precisa decidir onde esse filtro por Tenant acontece de fato (Application ou Infrastructure/repositório).
- A divergência entre `autonomy_level_required` (inteiro, Sigma Contracts) e `autonomyCapabilities` (mapa nomeado, Identity Engine) registrada em [ADR-0068](../adr/0068-autonomy-por-capability.md) precisa ser resolvida antes ou durante o Skill Engine (Release 8) — não bloqueia 3B, mas fica sinalizada.
