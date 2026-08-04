# Release 3A — Identity Domain

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 3 — aprovada para implementação.** Primeira metade da Release 3 — Identity Engine, dividida em duas sub-Releases sequenciais por decisão do Product Owner ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)): esta Proposal (3A) cobre só o **Domain**; a segunda metade ([0003b-identity-infrastructure.md](0003b-identity-infrastructure.md), 3B) só começa depois que esta estiver implementada e validada.

Nenhuma linha de código do Identity é escrita antes de quatro aprovações explícitas e separadas, todas já concedidas: (1) [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md); (2) [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md); (3) [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md); (4) esta Proposal.

Ver [ADR-0039](../adr/0039-identity-engine.md) (Identity Engine — extraído do Memory Engine), [ADR-0060](../adr/0060-release-dividida-em-sub-releases.md) (divisão 3A/3B), [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md) (quatro camadas DDD), [ADR-0062](../adr/0062-identity-nunca-conhece-outro-engine.md) (isolamento por eventos), [ADR-0063](../adr/0063-identificadores-como-value-objects.md) (identificadores como Value Object), [MULTITENANCY.md](../../MULTITENANCY.md), [WORKSPACES.md](../../WORKSPACES.md), [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) e [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) (design de referência que esta proposta implementa).

## Direção aprovada, reconciliação durante a Implementation

O Product Owner aprovou cinco refinamentos de direção sobre [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), sem exigir reabertura desta Proposal — cada um vira uma ADR própria durante a Implementation de 3A, não uma reescrita do modelo agora:

1. **`Identity` como objeto raiz** — `Identity → User → Workspace → Permissions → Context → Autonomy`, em vez do `Context` isolado como hoje descrito em IDENTITY_MODEL.md. Já adotado no vocabulário de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md).
2. **`Session` autentica uma `Identity`, não diretamente um `User`** — prepara múltiplas Sessions concorrentes por pessoa, uma por contexto (ex: Workspace Comercial e Workspace Financeiro simultâneos).
3. **`Context` imutável** — trocar de Workspace implica nova Session e novo Context, nunca mutação do vigente.
4. **`Team` tipado** — `System Team` (CTO, Developer, Support) vs. `Business Team` (Comercial, Financeiro, Obras), preparando automações futuras que discriminam por tipo de Team.
5. **`Autonomy` baseada em capacidade, não em nível numérico** — `CanApproveBudget`/`CanDeleteMission`/`CanDeploy` como booleanos nomeados, em vez de um inteiro 0–3 único. Mais flexível; substitui o esquema de "menor valor entre User/Role e Capability" de [SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva) por checagem direta de capability — o próprio ADR-0029 precisa ser revisitado quando isso for formalizado.

Estes cinco pontos afetam diretamente o modelo de domínio desta sub-Release — a Implementation já parte deles, e a ADR correspondente a cada um é escrita como parte do trabalho, não depois.

## Objetivo

Modelar completamente o domínio de identidade, em código — Value Objects, Entities, Aggregates, eventos de domínio, regras de negócio — sem nenhuma dependência de persistência, banco, autenticação ou API. Provar, através de teste automatizado puro (sem infraestrutura), que o modelo descrito em [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md)/[IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) é implementável e consistente **antes** de qualquer schema de banco ser desenhado — reduzindo o risco de retrabalho que motivou a divisão 3A/3B ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)).

## Escopo

**Existe:**
- `packages/identity-engine/Domain/` (ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)) — só esta camada nesta sub-Release.
- Value Objects para todo identificador ([ADR-0063](../adr/0063-identificadores-como-value-objects.md)): `TenantId`, `CompanyId`, `WorkspaceId`, `UserId`, `TeamId`, `RoleId`, `PermissionId`, `SessionId`, `IdentityId`.
- Entities/Aggregates de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md): `Tenant`, `Company`, `Workspace`, `User`, `Team`, `Role`, `Permission`, `RoleAssignment`, `Session`, e o agregado raiz `Identity` (ver "Direção aprovada" acima e [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md)).
- Regras de negócio como código testável, sem banco: resolução de Permissions/Autonomy efetivas a partir de `RoleAssignment`s (direto ou via Team); imutabilidade de `Context`; expiração de `Session`; a regra do menor valor entre Autonomy do User/Role e o exigido pela Capability ([SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva)) — ou sua substituição por capability nomeada, conforme a ADR de "Direção aprovada" item 5.
- Os dez eventos de domínio de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md), como classes de evento reais (`IdentityCreated`, `SessionStarted`, etc.) — publicados por método de domínio (ex: `Identity::authenticate()` produz um `SessionStarted`), mas **sem** publicação real no Event Bus ainda (isso depende de `IEventBus`, uma dependência de Application/Infrastructure — ver 3B).
- `contracts/Identity.contract.yaml` já publicado (Release anterior a esta Proposal) — validado contra a implementação real ao final desta sub-Release.

**Não existe ainda (fica para a Release 3B):** `Application/`, `Infrastructure/`, `Interface/` do Identity Engine — nada de persistência, repositório, `IdentityEngineModule`/`IModule`, `services/auth`, API HTTP, MariaDB, autenticação de verdade (hash de senha, tokens), publicação real no Event Bus. Esta sub-Release não sobe, não boota, não é alcançável de fora do próprio pacote — é testada só via testes automatizados de unidade sobre `Domain/`.

**Onde vive:**
- `packages/identity-engine/Domain/` — única pasta nova desta sub-Release.

## Arquitetura

Domain puro, sem dependência de framework, banco, HTTP ou até mesmo do Kernel (`IEventBus`, `IContainer` são consumidos por `Application`/`Infrastructure`, camadas de 3B — ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)). O agregado `Identity` (ver "Direção aprovada" item 1) expõe métodos que representam transições do fluxo de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) (ex: `authenticate()`, `selectWorkspace()`) e retorna os eventos de domínio correspondentes como valor de retorno — nunca os publica sozinho (publicar é responsabilidade de `Application`, em 3B, que recebe os eventos retornados e os entrega ao `IEventBus`).

Nenhuma dependência de `Domain/` aponta para `Application/`, `Infrastructure/` ou `Interface/` — só o sentido contrário é permitido ([ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)).

## Dependências

- Release 2 — SIGMA Bootstrap, aprovada e implementada.
- [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) aprovados — bloqueante, sem exceção.
- Nenhuma dependência de infraestrutura (banco, Redis) — é a primeira Release/sub-Release do projeto que não precisa de nada além de PHP puro para rodar seus testes.

## Riscos

1. **O modelo pode se revelar difícil de implementar como código puro** (ex: um agregado tentando fazer algo que só faz sentido com uma consulta ao banco) — exatamente o risco que esta divisão existe para descobrir cedo, antes de qualquer schema. Se acontecer, é tratado como um ajuste ao modelo, documentado como ADR, não como um retrofit de banco depois.
2. **Escopo pode crescer organicamente** dado que "modelar tudo" é uma tentação de já pensar em persistência — mitigado pela lista fechada de "Escopo" acima: qualquer menção a banco/HTTP/autenticação de verdade nesta sub-Release é, por definição, escopo da 3B, não desta.

## Entregáveis

- `packages/identity-engine/Domain/` implementado — Value Objects, Entities, Aggregates, eventos de domínio.
- Testes de unidade cobrindo toda regra de negócio listada em "Escopo", sem nenhuma dependência de infraestrutura.
- **Decision Log** (`docs/releases/0003a-identity-domain-decision-log.md`).
- **Validation Report** (`docs/releases/0003a-identity-domain-validation-report.md`) — obrigatório desde já, ver [ADR-0056](../adr/0056-validation-report-obrigatorio.md). Nesta sub-Release, as seções "Docker" e "HTTP" do template são preenchidas como "Não aplicável a esta sub-Release — ver 3B", nunca omitidas silenciosamente.

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- Cada Value Object rejeita construção com valor inválido (ex: `TenantId` vazio).
- `RoleAssignment` resolve corretamente Permissions/Autonomy efetivas, inclusive quando o User recebe o Role via Team, não diretamente.
- Regra do menor valor entre Autonomy do User/Role e o exigido pela Capability (ou a versão por capability nomeada, conforme a ADR de "Direção aprovada" item 5).
- `Session` expirada não produz um `Context`/`Identity` válido.
- Cada transição de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) produz o evento de domínio correto de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md).

### 2. Architecture Validation
- Nenhuma classe em `Domain/` importa algo de `Application/`, `Infrastructure/` ou `Interface/`, nem de `packages/kernel`, nem de nenhuma biblioteca de banco/HTTP.
- Todo identificador é um Value Object — nenhuma `string` primitiva usada como identificador em assinatura de método público.
- Implementação consistente com [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md)/[IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) — nenhuma entidade nova introduzida sem passar primeiro por uma revisão do modelo.

### 3. Scenario Validation
- Um `Identity` autenticado, Workspace selecionado, com Role atribuído só via Team — Permissions/Autonomy resolvidas corretamente, tudo em memória, sem banco.
- Trocar de Workspace produz uma nova Session/Context — o `Context` anterior permanece inalterado (imutabilidade, "Direção aprovada" item 3).
- Uma tentativa de autenticar uma `Identity` desativada (`IdentityDisabled`) é rejeitada pelo próprio agregado, antes de qualquer camada de infraestrutura existir para impedir isso de outra forma.

## Critérios de Aceite

- Todas as dez entidades de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md), com os cinco refinamentos de direção aprovados, existem como código em `Domain/`, com as relações implementadas exatamente como modeladas.
- Todo identificador é um Value Object.
- Os dez eventos de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) existem como classes de evento, produzidos corretamente por cada transição correspondente.
- 100% dos testes desta sub-Release rodam sem nenhuma infraestrutura (nem banco, nem Redis, nem HTTP) — é o próprio Critério de Aceite que prova que a divisão 3A/3B foi respeitada.
- Os três níveis de validação executados e documentados no Validation Report de 3A.
