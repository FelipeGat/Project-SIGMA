# Release 3 — Identity Engine

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 2 — aprovada para implementação.** Aprovada mediante a inclusão de dois documentos, ambos agora entregues: [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) e `contracts/Identity.contract.yaml`. Nenhuma linha de código do Identity é escrita antes de três aprovações explícitas e separadas: (1) [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md); (2) esta Proposal; (3) os dois documentos acima — todas já concedidas.

Ver [ADR-0039](../adr/0039-identity-engine.md) (Identity Engine — extraído do Memory Engine), [MULTITENANCY.md](../../MULTITENANCY.md), [WORKSPACES.md](../../WORKSPACES.md), [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) e [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) (design de referência que esta proposta implementa).

## Direção aprovada, reconciliação durante a Implementation

O Product Owner aprovou cinco refinamentos de direção sobre [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), sem exigir reabertura desta Proposal — cada um vira uma ADR própria durante a Implementation, não uma reescrita do modelo agora:

1. **`Identity` como objeto raiz** — `Identity → User → Workspace → Permissions → Context → Autonomy`, em vez do `Context` isolado como hoje descrito em IDENTITY_MODEL.md. Já adotado no vocabulário de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md).
2. **`Session` autentica uma `Identity`, não diretamente um `User`** — prepara múltiplas Sessions concorrentes por pessoa, uma por contexto (ex: Workspace Comercial e Workspace Financeiro simultâneos).
3. **`Context` imutável** — trocar de Workspace implica nova Session e novo Context, nunca mutação do vigente.
4. **`Team` tipado** — `System Team` (CTO, Developer, Support) vs. `Business Team` (Comercial, Financeiro, Obras), preparando automações futuras que discriminam por tipo de Team.
5. **`Autonomy` baseada em capacidade, não em nível numérico** — `CanApproveBudget`/`CanDeleteMission`/`CanDeploy` como booleanos nomeados, em vez de um inteiro 0–3 único. Mais flexível; substitui o esquema de "menor valor entre User/Role e Capability" de [SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva) por checagem direta de capability — o próprio ADR-0029 precisa ser revisitado quando isso for formalizado.

Estes cinco pontos afetam diretamente o schema desta Release (Escopo, abaixo) — a Implementation já parte deles, e a ADR correspondente a cada um é escrita como parte do trabalho, não depois.

## Objetivo

Responder "quem" para todo o resto do SIGMA: dar existência real (schema, persistência, resolução) às dez entidades definidas em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) — Tenant, Company, Workspace, User, Team, Role, Permission, Autonomy Level, Context, Session — e disponibilizar o Context resolvido a qualquer Engine seguinte através do Kernel. Primeiro Engine de domínio real do SIGMA — primeira Release com persistência (MariaDB) e o primeiro `IModule` que não é infraestrutura pura.

## Escopo

**Existe:**
- Schema de banco (MariaDB) para as dez entidades de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) — `tenants`, `companies`, `workspaces`, `users`, `teams`, `team_memberships`, `workspace_memberships`, `roles`, `permissions`, `role_permissions`, `role_assignments`, `sessions`. `tenant_id` obrigatório desde a primeira migration, exceto na própria tabela `tenants` ([ADR-0021](../adr/0021-multitenancy-desde-o-schema.md)).
- `IdentityEngineModule` — primeiro `IModule` de domínio, validando se o contrato genérico (`packages/kernel/src/Contract/IModule.php`) se sustenta fora de infraestrutura pura (risco já sinalizado no Decision Log da Release 2).
- Resolução de Context: dado um token de Session válido, resolver User → Tenant/Company/Workspace ativos → Roles (do User e dos Teams de que participa, válidos no escopo) → Permissions efetivas → Autonomy Level efetivo — exatamente como especificado em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md#context).
- Autenticação mínima: emissão de Session via credencial (e-mail/senha, hash com `password_hash`/Argon2id — nunca implementação própria de hashing), validação de Session, expiração, logout (invalidação).
- `services/auth` — casca HTTP: `POST /auth/login`, `POST /auth/logout`, `GET /auth/context` (resolve e retorna o Context vigente a partir do token), todas as respostas no [Envelope](../../SIGMA_PROTOCOL.md#1-o-envelope).
- CRUD mínimo, via código (sem interface Web), para popular Tenant/Company/Workspace/User/Team/Role/Permission/RoleAssignment o suficiente para os cenários de Scenario Validation abaixo.
- `docker-compose.yml` ganha o serviço `mariadb` — primeira Release com banco no ambiente local.

**Não existe ainda:** Mission, Memory, Planner, Intent, Skill, Agent, Execution, Audit, Plugins de negócio, interface Web/mobile, OAuth/SSO/2FA, Capability Registry enforcement (chega com o Skill Engine, Release 8), billing/onboarding self-service de novo Tenant. Autenticação é deliberadamente mínima — o suficiente para Session/Context existirem de verdade, não um sistema de auth completo.

**Onde vive:**
- `packages/identity-engine` — entidades de domínio, interfaces de repositório, `IdentityEngineModule`, lógica de resolução de Context.
- `services/auth` — casca HTTP mínima (login/logout/context), consumindo `packages/identity-engine` como `services/gateway` consome `packages/kernel`.
- `docker/docker-compose.yml` atualizado com `mariadb`.

## Arquitetura

`IdentityEngineModule implements IModule` (`kind: Engine`), registrado no System Manifest junto de `kernel` e `event-bus`. `services/auth` depende de `packages/identity-engine` e de `packages/kernel` (`IContainer`, `ILogger`, `IEventBus`) — nunca conhece `RedisEventBus`/MariaDB diretamente fora de `packages/identity-engine`, mesmo princípio de dependência só por interface já usado em `services/event-bus` (ver [ADR-0057](../adr/0057-eventbus-composicao-inmemory.md)).

**Pergunta em aberto para a Architecture Review** (não decidida nesta Proposal, deliberadamente): qual camada de acesso a dados usar em `packages/identity-engine` — PDO puro com um runner de migration mínimo (mantém o precedente "framework-agnóstico" de `packages/kernel`, ver Decision Log da Release 2), ou uma biblioteca como Doctrine DBAL (menos código próprio, uma dependência a mais). Recomendação: PDO puro + runner próprio, pelo mesmo motivo que `packages/kernel` não usa `illuminate/container` — mas isso é uma recomendação, não uma decisão já tomada nesta Proposal.

## Dependências

- Release 2 — SIGMA Bootstrap, aprovada e implementada (`IModule`, `IContainer`, `IEventBus`, System Manifest Loader com `manifestVersion`).
- [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) aprovado — bloqueante, sem exceção.
- MariaDB disponível no ambiente de desenvolvimento (`docker-compose.yml` resolve isso localmente, mesmo padrão do Redis na Release 2).

## Riscos

1. **Primeiro `IModule` de domínio real** — pode revelar que o contrato genérico (`dependsOn`/`configSchema`/`register`/`boot`/`describe`) não é suficiente para um Engine com persistência (ex: migrations rodam em que etapa do Lifecycle?). Mitigado por tratar isso como parte central da Implementation, não um detalhe — se o contrato não se sustentar, isso vira uma ADR nova, não um ajuste silencioso.
2. **Primeira persistência do projeto** — schema errado agora é caro de corrigir depois (dados reais dependem dele). Mitigado por [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) já ter passado por revisão antes de qualquer migration ser escrita.
3. **Superfície de autenticação, mesmo mínima, é sensível por natureza** — mitigado por escopo deliberadamente restrito (sem OAuth/SSO/2FA nesta Release) e por usar apenas primitivas de hashing padrão da linguagem, nunca implementação própria.
4. **Escopo pode crescer organicamente** dado quão fundacional este Engine parece — mitigado pela lista fechada de "Escopo" acima, mesmo padrão disciplinar da Release 2 ([ADR-0053](../adr/0053-escopo-restrito-release-2.md)).

## Entregáveis

- `packages/identity-engine` implementado — entidades, repositórios, `IdentityEngineModule`, resolução de Context.
- Migrations para as doze tabelas listadas em "Escopo".
- `services/auth` com `POST /auth/login`, `POST /auth/logout`, `GET /auth/context`.
- `docker/docker-compose.yml` com `mariadb`.
- `system-manifest.yaml` atualizado com o Module `identity-engine`.
- `contracts/Session.contract.yaml` ou equivalente (a definir na Architecture Review se um Contract por entidade ou um só para o Engine).
- **Decision Log** (`docs/releases/0003-identity-engine-decision-log.md`).
- **Validation Report** (`docs/releases/0003-identity-engine-validation-report.md`) — obrigatório desde já, ver [ADR-0056](../adr/0056-validation-report-obrigatorio.md).

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- Cada entidade de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) persiste e é lida corretamente, com `tenant_id` sempre presente onde aplicável.
- `RoleAssignment` resolve corretamente Permissions/Autonomy Level efetivos, inclusive quando o User recebe o Role via Team, não diretamente.
- Regra do menor valor entre Autonomy Level do User/Role e o exigido pela Capability ([SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva)).
- Session expira corretamente; Session inválida/expirada não resolve Context.

### 2. Architecture Validation
- `services/auth` não importa nenhuma classe concreta do Kernel fora das seis interfaces.
- Nenhuma query de domínio monta filtro de Tenant manualmente fora do ponto central de resolução de Context.
- Implementação consistente com [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) — nenhuma entidade nova introduzida sem passar primeiro por uma revisão do modelo.

### 3. Scenario Validation
- Um Tenant com duas Companies, cada uma com Workspaces próprios — isolamento entre Tenants nunca vaza (query de um Tenant não retorna dado de outro).
- Um User com Roles diferentes em dois Workspaces diferentes — Autonomy Level efetivo muda conforme o Workspace ativo do Context.
- Um User que recebe Permission apenas via Team (não via RoleAssignment direto) — Context resolve corretamente mesmo assim.
- Login com credencial inválida — rejeitado explicitamente, sem Session emitida.
- Session expirada — `GET /auth/context` rejeita explicitamente.
- `docker-compose up` real, com MariaDB — validado de fato (não repetir a pendência da Release 2, ver [Validation Report da Release 2](0002-sigma-bootstrap-validation-report.md)).

## Critérios de Aceite

- Todas as dez entidades de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) existem, persistidas, com as relações descritas lá implementadas exatamente como modeladas.
- `IdentityEngineModule` sobe via Bootstrap (Release 2) sem nenhuma mudança no contrato `IModule`, ou — se precisar de mudança — essa mudança é uma ADR nova, discutida separadamente.
- Um Context correto é resolvido a partir de uma Session válida, refletindo Tenant/Company/Workspace/Permissions/Autonomy Level.
- Os três níveis de validação executados e documentados no Validation Report.
