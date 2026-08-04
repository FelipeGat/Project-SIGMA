# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 5C — Mission Infrastructure: Proposal apresentada, aguardando aprovação.** [docs/releases/0005c-mission-infrastructure.md](../docs/releases/0005c-mission-infrastructure.md) — `Application/`/`Infrastructure/`/`Interfaces/` sobre o `Domain/` já validado em 5B. Diferente da Release 4B (Memory), **sem worker/listener Redis**: nenhum Engine anterior publica eventos que o Mission Engine precise consumir ainda (Planner/Intent não existem, ADR-0031) — os treze eventos são publicados por `Application/`, nenhum é assinado. Cinco tabelas propostas (`missions` com `tenant_id`, `subtasks`/`approval_gates`/`compensations`/`mission_history` referenciando `mission_id` via FK, mesmo padrão de `sessions` no Identity Engine); `Plan` persistido como `JSON` (imutável, nunca consultado por campo próprio). Nenhum código ainda — aguardando aprovação explícita antes de `Application/`/`Infrastructure/`/`Interfaces/`, desta vez com Proposal escrita e apresentada antes do código (diferente da 5B, que reaproveitou a aprovação da 5A — ver nota de processo na própria Proposal 5C).

**Release 5A + 5B — Mission Research + Domain: COMPLETAS.** `packages/mission-engine/src/Domain/` implementado — `Identifier` (cópia própria) + sete Value Objects de identificador, seis enums, `Actor`/`Plan`/`SubtaskCandidate`/`RetryAttempt`/`Compensation`/`MissionHistoryEntry`, entidades `Subtask`/`ApprovalGate` (vivendo dentro do limite do aggregate), os treze eventos de [MISSION_EVENTS.md](../MISSION_EVENTS.md) (`MissionFailed` foi um achado real desta Implementation — o caminho "falha sem compensação" não tinha evento, catalogado antes do código, mesma disciplina de `MemoryReactivated` na 4A), e o aggregate `Mission` com todos os métodos de transição dos quatro fluxos de [MISSION_LIFECYCLE.md](../MISSION_LIFECYCLE.md). 37 testes, 103 assertions, 100% verde. Architecture Validation confirmou zero dependência de `planner-engine` ou qualquer outro Engine (ADR-0092 na prática). Duas decisões de modelagem novas, tomadas durante a Implementation (ver [Decision Log 5B](../docs/releases/0005b-mission-implementation-decision-log.md)): `Mission::advanceToNextSubtask()` unifica criação de Subtask + avaliação de gate de autonomia num único método (mais fiel a MISSION_LIFECYCLE.md que o par `addSubtask()`/`evaluateApproval()` cogitado inicialmente); `Subtask::compensate()` aceita origem `Failed` **ou** `Validated` (uma validação final reprovada pode apontar uma Subtask já `Validated` como origem do efeito a compensar). 5B decidiu não se subdividir mais — `Application`/`Infrastructure`/`Interfaces` do Mission Engine ainda não têm Release nomeada. Ver [Decision Log 5A](../docs/releases/0005a-mission-research-decision-log.md), [Decision Log 5B](../docs/releases/0005b-mission-implementation-decision-log.md)/[Validation Report 5B](../docs/releases/0005b-mission-implementation-validation-report.md).

**Release 4.5 — Platform Validation: COMPLETA.** Dez verificações executadas contra `docker compose up --build` real — stress test, restart de containers, perda de Redis/Worker, 20 usuários concorrentes, Memory Promotion em volume, Twin Sync sequencial, latência, event replay, benchmark. Achado real de alta prioridade, não previsto na Proposal: nenhum serviço do `docker-compose.yml` tem `restart policy` — `memory-worker` não recupera sozinho de nenhuma interrupção de conexão Redis. Dois achados esperados confirmados com evidência real: perda de mensagem durante queda do Worker é definitiva (sem fila/replay); Redis pub/sub não tem event replay. Ver [Decision Log](../docs/releases/0004.5-platform-validation-decision-log.md)/[Validation Report](../docs/releases/0004.5-platform-validation-validation-report.md). Pergunta de numeração Planner×Intent (6/7) encerrada: mantido ADR-0031 sem alteração. **Release 4 — Memory Engine: COMPLETA (4A + 4B implementadas e validadas).** `packages/memory-engine` tem as quatro camadas DDD completas. `services/memory-worker` (novo) sincroniza `UserTwin` de fato, cross-processo, a partir dos eventos que `services/auth` já publica — provado via `docker compose up --build` real com dois containers distintos. Nenhuma API HTTP pública para o Memory Engine ainda (decisão deliberada — sem consumidor real).

## O que existe (documentação)

- Tudo das rodadas anteriores (MEMORY_MODEL.md/MEMORY_LIFECYCLE.md revisão 2, MEMORY_PROMOTION_RULES.md, ADRs 0082-0088).
- **[Proposal 4A](../docs/releases/0004a-memory-domain.md)** e **[Proposal 4B](../docs/releases/0004b-memory-infrastructure.md)** — ambas aprovadas e implementadas.
- **Decision Logs e Validation Reports de 4A e 4B** publicados — o de 4B documenta o achado mais importante da Release: `RedisEventBus` nunca teve entrega cross-processo real (só local), lacuna sinalizada desde a Release 2/ADR-0057, resolvida com `RedisSubscriber` (`services/event-bus`) + `services/memory-worker`.
- **`packages/memory-engine/VERSION.md`** (novo) — SemVer formalizado, `1.0.0`.
- **`CHANGELOG.md`** — entrada "Release 4 — Memory" adicionada (linguagem de produto).
- `DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`/`contracts/Memory.contract.yaml` com doze eventos (o décimo segundo, `MemoryReactivated`, catalogado durante a Implementation da 4A).
- **[MISSION_MANIFESTO.md](../MISSION_MANIFESTO.md)/[MISSION_MODEL.md](../MISSION_MODEL.md)/[MISSION_LIFECYCLE.md](../MISSION_LIFECYCLE.md)/[MISSION_EVENTS.md](../MISSION_EVENTS.md)**, `contracts/Mission.contract.yaml`, ADRs 0089-0093 (Release 5A) — todos aprovados.
- **[Proposal 5B](../docs/releases/0005b-mission-implementation.md)** — implementada. `EVENT_MODEL.md`/`EVENT_CATALOG.md`/`MISSION_EVENTS.md`/`contracts/Mission.contract.yaml` com treze eventos (o décimo terceiro, `MissionFailed`, catalogado durante a Implementation da 5B).

## O que existe (código)

- **`packages/memory-engine/src/`** completo: `Domain/` (4A), `Application/` (13 casos de uso, 5 interfaces de repositório, `PinnedMemorySubject`), `Infrastructure/` (5 repositórios `Pdo*`, migrations, `KnowledgeFolderIndexer`), `Interfaces/MemoryEngineModule`.
- **`services/event-bus/src/RedisSubscriber.php`** (novo) + `RedisEventBus::dispatchLocally()` — primeiro listener Redis cross-processo real do projeto.
- **`services/memory-worker`** (novo serviço, sem HTTP) — `Bootstrap.php`, `bin/worker.php`.
- **`packages/mission-engine/src/Domain/`** completo (novo) — `Identifier` + sete IDs, seis enums, seis Value Objects de suporte, `Subtask`/`ApprovalGate`, aggregate `Mission`, treze eventos. 38 arquivos `.php`, 37 testes.
- **232 testes automatizados no monorepo** (195 até a Release 4.5 + 37 da 5B), todos passando; 21 `Skipped` (infraestrutura Docker/MariaDB/Redis indisponível nesta sessão de validação).
- `docker/memory-worker.Dockerfile`, `docker-compose.yml` com o serviço `memory-worker`, `system-manifest.yaml` com o Module `memory-engine`.

## Decisões de Implementation da Release 4B

- **Achado real que mudou o escopo em relação ao placeholder original**: `RedisEventBus`/`InMemoryEventBus` nunca entregavam eventos entre processos, só localmente — resolvido com `RedisSubscriber` (`pubSubLoop` bloqueante) + `services/memory-worker`.
- **Achado real durante a validação via Docker**: `memory-worker` morria sozinho após ~60s ocioso (timeout padrão de socket do PHP aplicado à conexão Redis do subscriber) — corrigido com `read_write_timeout: -1`. Sem este ajuste, o worker seria inutilizável em produção.
- **`ProjectDigitalTwinFromEvent` usa `identityId` como `externalRef`**, não `userId` como MEMORY_MODEL.md revisão 2 dizia literalmente — refinamento necessário porque `workspace.selected` só carrega `identityId`.
- **`PinnedMemorySubject` vive em `Application/`**, fechando a pendência da 4A.
- **Nenhuma API HTTP pública para o Memory Engine** — decisão deliberada, sem consumidor real ainda.
- **`KnowledgeFolderIndexer` implementado, sem gatilho de execução em produção** — pendência sinalizada, não resolvida.

## Decisões de Implementation da Release 5B

- **Achado real**: faltava evento para "Subtask falha sem produzir efeito" — `MissionFailed` catalogado antes do código (mesma disciplina de `MemoryReactivated` na 4A).
- **`Mission::advanceToNextSubtask()`** substitui o par `addSubtask()`/`evaluateApproval()` cogitado inicialmente — um único método reflete literalmente como MISSION_LIFECYCLE.md descreve "puxar a próxima Subtask candidata do Plan" como um evento de negócio só.
- **`completeValidation()` virou `passValidation()`/`failValidation()`** — dois desfechos distintos do Fluxo 4, mesmo padrão de `approve()`/`reject()`.
- **`failValidation()` exige um `SubtaskId $faultingSubtaskId`** — achado de modelagem: uma validação final reprovada não tem uma Subtask "atualmente falhando" automática (todas já `Validated`), então quem chama precisa apontar qual.
- **`Subtask::compensate()` passou a aceitar origem `Failed` ou `Validated`** — consequência direta da decisão acima.
- **`Mission.history`** implementado como `list<MissionHistoryEntry>` (`status`+`at`) — o menor tipo que satisfaz literalmente MISSION_MODEL.md, sem inventar campos extras.
- **5B decidiu não se subdividir mais** — `Application`/`Infrastructure`/`Interfaces` do Mission Engine ficam para uma Release futura, ainda sem número.

## Pendências / riscos sinalizados

- Mesmas de sempre (PHP 8.2, `autonomy_level_required` vs. `autonomyCapabilities`, `PermissionId` sem uso, migrations lazy, numeração Release 6/7).
- `Identifier` duplicada entre `identity-engine`/`memory-engine` — consolidação em `packages/core` ainda recomendada, não decidida.
- `KnowledgeFolderIndexer` sem gatilho de execução automática.
- `handleWorkspaceSelected()` ignora silenciosamente entrega fora de ordem — sem retry/fila.
- `read_write_timeout: -1` é correção pragmática, não estratégia de reconexão robusta — revisitar quando o Scheduler existir.

## Bloqueios

**Aguardando aprovação da Proposal da Release 5C — Mission Infrastructure** ([docs/releases/0005c-mission-infrastructure.md](../docs/releases/0005c-mission-infrastructure.md)) — nenhum código de `Application/`/`Infrastructure/`/`Interfaces/` antes disso. Push do(s) commit(s) pendentes também aguardando confirmação explícita (mesma regra de sempre). Ver [NEXT.md](../memory/NEXT.md).
