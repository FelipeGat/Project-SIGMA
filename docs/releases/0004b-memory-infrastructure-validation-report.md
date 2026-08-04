# Release 4B — Memory Infrastructure — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0004b-memory-infrastructure.md](0004b-memory-infrastructure.md) (revisão 1).

## Release

Release 4B — Memory Infrastructure.

## Ambiente

- Windows 10 Pro, XAMPP + Docker Desktop 4.79.0 (Docker Compose v5.1.4).
- Execução: 2026-08-04.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12` local / `8.2-cli-alpine` nos containers.
- Mesmo gap já aceito conscientemente desde a Release 2 — reconciliação adiada para a Release de CI/CD.

## Docker

**`docker compose up --build` executado de fato**, com `--no-cache` nas imagens novas/alteradas (`memory-worker`, `auth`). Cinco containers: `redis`, `mariadb`, `gateway`, `auth`, `memory-worker` — todos subiram e ficaram saudáveis.

**Achado real corrigido durante esta validação**: a primeira subida do `memory-worker` funcionava (registrava os handlers, log "pronto, aguardando...") mas o processo morria sozinho com `Predis\Connection\ConnectionException` após ~60 segundos ocioso — o timeout padrão de socket do PHP aplicado a uma conexão que fica bloqueada em `pubSubLoop()`. Corrigido com `read_write_timeout: -1` na conexão dedicada do worker (ver Decision Log). Após a correção, o container permaneceu de pé por mais de 100 segundos ocioso sem cair, confirmado antes de prosseguir.

## HTTP

**Não aplicável a esta sub-Release** — nenhuma API HTTP pública para o Memory Engine (decisão deliberada de escopo, ver Proposal). `services/auth` (já existente, Release 3B) foi usado para *originar* os eventos consumidos, não para expor nada novo do Memory Engine.

## Testes

| Pacote/Serviço | Comando | Testes | Assertions | Skipped (sem infra local) |
|---|---|---|---|---|
| `packages/core` | `composer test` | 8 | 13 | 0 |
| `packages/kernel` | `composer test` | 36 | 78 | 0 |
| `services/event-bus` | `composer test` | 10 | 17 | 0 |
| `services/gateway` | `composer test` | 8 | 22 | 0 |
| `packages/identity-engine` | `composer test` | 72 | 96 | 10 |
| `services/auth` | `composer test` | 5 | 0 | 5 |
| `packages/memory-engine` | `composer test` | 55 | 140–157¹ | 0–5¹ |
| `services/memory-worker` | `composer test` | 1 | 0–2¹ | 0–1¹ |

¹ Executados duas vezes: sem infraestrutura local (5 testes de Infrastructure/1 de Bootstrap pulados, `markTestSkipped` explícito) e novamente com as variáveis `MEMORY_TEST_DB_*` apontando para a MariaDB real do `docker-compose` (`127.0.0.1:13306`, banco `sigma_memory_test` criado para isolar do `sigma_identity_test` do Identity Engine) — nessa segunda rodada, **0 testes pulados, 100% executados de fato** (157 assertions em `memory-engine`, 2 em `memory-worker`).

**Total do monorepo**: 195 testes, todos passando (21 skipped apenas na configuração sem infraestrutura local — 0 skipped quando apontados para a MariaDB real via Docker).

## Coverage

Não medida nesta Release — mesma pendência de todas as anteriores.

## Scenario Validation

Cenários listados na Proposal (revisão 1), cada um com o resultado real:

- ✅ **`docker compose up --build` real, com `memory-worker` como container separado de `auth` — subida completa validada.** Cinco containers saudáveis; achado do timeout corrigido antes de prosseguir (ver "Docker" acima).
- ✅ **Login via `services/auth` → `identity.created`/`workspace.selected` publicados no Redis real → `memory-worker`, em processo/container separado, recebe e persiste `UserTwin`.** Executado com um script de seed rodando dentro do container `auth` (`RegisterIdentity` → `Authenticate` → `SelectWorkspace`, os mesmos casos de uso já validados na Release 3B), consultando a tabela `digital_twins` diretamente depois:
  ```
  id: ddbf7cf0-...
  subject_type: user
  external_ref: 39fb5cfc-... (identityId)
  state: {"identityId":"39fb5...","userId":"019af1...","workspaceId":"5a8670..."}
  last_synced_at: 2026-08-04 18:59:51
  ```
  `identityId`/`userId`/`workspaceId` todos presentes e corretos — a entrega cross-processo funcionou de ponta a ponta.
- ✅ **Reiniciar o container `memory-worker` não perde `UserTwin`s já persistidos.** `docker compose restart memory-worker` executado; `SELECT COUNT(*) FROM digital_twins` continuou retornando `1` depois do restart.
- ✅ **Indexar um arquivo real de `/knowledge` produz um `KnowledgeRecord` consultável; indexar de novo o mesmo arquivo produz `version: 2`.** Validado via `KnowledgeFolderIndexerTest` (arquivo real em disco, `InMemoryKnowledgeRecordRepository`) e via `PdoRepositoriesTest::test_knowledge_record_versions_are_never_overwritten` (MariaDB real) — não validado como parte do fluxo Docker (ver Pendências: nenhum gatilho de execução em produção existe ainda para o indexador).

## Pendências

- Coverage de código não medido.
- **`KnowledgeFolderIndexer` não tem gatilho de execução em produção** — nem `MemoryEngineModule` nem `services/memory-worker` o chamam automaticamente hoje. Implementado e testado (unidade + MariaDB real), mas "quando/como isso roda de fato" (comando CLI manual, cron via Scheduler futuro) não foi decidido nesta Release.
- **`handleWorkspaceSelected()` ignora silenciosamente entrega fora de ordem** (`workspace.selected` chegando antes de `identity.created` ter sido processado) — sem retry/fila, aceito conscientemente (ver Decision Log).
- `Identifier` continua duplicada entre `identity-engine` e `memory-engine` (pendência da 4A, não resolvida aqui).
- Três Permissions (`memory.promote`/`memory.block_promotion`/`knowledge.curate`) continuam só vocabulário — nenhuma checagem real, sem API pública que precise delas ainda.
- `read_write_timeout: -1` é uma correção pragmática, não uma estratégia de reconexão robusta — revisitar quando o Scheduler existir (ver Decision Log).
