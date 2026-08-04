# ADR-0053: Escopo restrito da Release 2 — lista explícita do que existe e do que não existe

- **Status**: Aceito — refina [ADR-0038](0038-sigma-bootstrap-nao-kernel-completo.md)
- **Data**: 2026-08-04

## Contexto

O escopo da Release 2 já excluía domínio (Mission, Intent, Skill, Agent). Em revisão de arquitetura, ficou claro que a lista precisava ser mais explícita — em particular, se o ambiente local incluiria banco de dados e se isso, sozinho, já seria "domínio demais" para um Bootstrap que deveria ser infraestrutura pura.

## Decisão

Escopo da Release 2, explícito:

**Existe**: Bootstrap, Container de DI, Module Loader, Configuration Provider, Lifecycle Manager, Health Manager, Event Bus (infraestrutura apenas — mecanismo de publish/subscribe, sem eventos de domínio), Logger/Telemetry, System Manifest Loader.

**Não existe ainda**: IA, Missions, Planner, Intent, Memory, Identity, Plugins de negócio, Skills, Agents, **banco de dados**, autenticação, interface Web.

A exclusão de banco de dados nesta lista é deliberada e vai além do que a proposta original (revisão 1) já excluía: sem nenhuma entidade de domínio ainda, não há o que persistir em MariaDB — incluir a conexão de banco nesta Release seria infraestrutura sem propósito imediato, testável apenas de forma artificial. `docker-compose.yml` desta Release inclui Redis (necessário para o Event Bus), não MariaDB.

## Consequências

- A Release 2 fica objetivamente menor e mais fácil de validar — cada item da lista "não existe ainda" é um Critério de Aceite verificável (busca no diff), não uma interpretação.
- MariaDB entra no ambiente local apenas quando a primeira Release que de fato persiste algo (Identity Engine, Release 3) o exigir — evita infraestrutura ociosa.
- `docs/releases/0002-sigma-bootstrap.md` é atualizada para remover MariaDB do escopo e do `docker-compose.yml`.
