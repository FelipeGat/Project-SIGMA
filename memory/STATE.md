# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 4 — Memory Engine: COMPLETA (4A + 4B implementadas e validadas).** `packages/memory-engine` tem as quatro camadas DDD completas. `services/memory-worker` (novo) sincroniza `UserTwin` de fato, cross-processo, a partir dos eventos que `services/auth` já publica — provado via `docker compose up --build` real com dois containers distintos. Nenhuma API HTTP pública para o Memory Engine ainda (decisão deliberada — sem consumidor real).

## O que existe (documentação)

- Tudo das rodadas anteriores (MEMORY_MODEL.md/MEMORY_LIFECYCLE.md revisão 2, MEMORY_PROMOTION_RULES.md, ADRs 0082-0088).
- **[Proposal 4A](../docs/releases/0004a-memory-domain.md)** e **[Proposal 4B](../docs/releases/0004b-memory-infrastructure.md)** — ambas aprovadas e implementadas.
- **Decision Logs e Validation Reports de 4A e 4B** publicados — o de 4B documenta o achado mais importante da Release: `RedisEventBus` nunca teve entrega cross-processo real (só local), lacuna sinalizada desde a Release 2/ADR-0057, resolvida com `RedisSubscriber` (`services/event-bus`) + `services/memory-worker`.
- **`packages/memory-engine/VERSION.md`** (novo) — SemVer formalizado, `1.0.0`.
- **`CHANGELOG.md`** — entrada "Release 4 — Memory" adicionada (linguagem de produto).
- `DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`/`contracts/Memory.contract.yaml` com doze eventos (o décimo segundo, `MemoryReactivated`, catalogado durante a Implementation da 4A).

## O que existe (código)

- **`packages/memory-engine/src/`** completo: `Domain/` (4A), `Application/` (13 casos de uso, 5 interfaces de repositório, `PinnedMemorySubject`), `Infrastructure/` (5 repositórios `Pdo*`, migrations, `KnowledgeFolderIndexer`), `Interfaces/MemoryEngineModule`.
- **`services/event-bus/src/RedisSubscriber.php`** (novo) + `RedisEventBus::dispatchLocally()` — primeiro listener Redis cross-processo real do projeto.
- **`services/memory-worker`** (novo serviço, sem HTTP) — `Bootstrap.php`, `bin/worker.php`.
- **195 testes automatizados no monorepo** (135 até a Release 3.5 + 35 da 4A + 60 novos da 4B — Application/Infrastructure/RedisSubscriber/Bootstrap), todos passando; rodados também contra MariaDB real via Docker (0 skips nessa configuração).
- `docker/memory-worker.Dockerfile`, `docker-compose.yml` com o serviço `memory-worker`, `system-manifest.yaml` com o Module `memory-engine`.

## Decisões de Implementation da Release 4B

- **Achado real que mudou o escopo em relação ao placeholder original**: `RedisEventBus`/`InMemoryEventBus` nunca entregavam eventos entre processos, só localmente — resolvido com `RedisSubscriber` (`pubSubLoop` bloqueante) + `services/memory-worker`.
- **Achado real durante a validação via Docker**: `memory-worker` morria sozinho após ~60s ocioso (timeout padrão de socket do PHP aplicado à conexão Redis do subscriber) — corrigido com `read_write_timeout: -1`. Sem este ajuste, o worker seria inutilizável em produção.
- **`ProjectDigitalTwinFromEvent` usa `identityId` como `externalRef`**, não `userId` como MEMORY_MODEL.md revisão 2 dizia literalmente — refinamento necessário porque `workspace.selected` só carrega `identityId`.
- **`PinnedMemorySubject` vive em `Application/`**, fechando a pendência da 4A.
- **Nenhuma API HTTP pública para o Memory Engine** — decisão deliberada, sem consumidor real ainda.
- **`KnowledgeFolderIndexer` implementado, sem gatilho de execução em produção** — pendência sinalizada, não resolvida.

## Pendências / riscos sinalizados

- Mesmas de sempre (PHP 8.2, `autonomy_level_required` vs. `autonomyCapabilities`, `PermissionId` sem uso, migrations lazy, numeração Release 6/7).
- `Identifier` duplicada entre `identity-engine`/`memory-engine` — consolidação em `packages/core` ainda recomendada, não decidida.
- `KnowledgeFolderIndexer` sem gatilho de execução automática.
- `handleWorkspaceSelected()` ignora silenciosamente entrega fora de ordem — sem retry/fila.
- `read_write_timeout: -1` é correção pragmática, não estratégia de reconexão robusta — revisitar quando o Scheduler existir.

## Bloqueios

Nenhum bloqueio ativo. Push do(s) commit(s) desta rodada aguardando confirmação explícita (mesma regra de sempre). Próximo passo natural: Release 5 (Planner, conforme ADR-0031 — ou Intent, ver pergunta em aberto sobre numeração 6/7) ou uma Release de consolidação, a critério do Product Owner. Ver [NEXT.md](../memory/NEXT.md).
