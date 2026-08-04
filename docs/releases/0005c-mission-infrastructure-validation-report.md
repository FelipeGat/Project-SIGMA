# Release 5C — Mission Infrastructure — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0005c-mission-infrastructure.md](0005c-mission-infrastructure.md) (revisão 1).

## Release

Release 5C — Mission Infrastructure.

## Ambiente

- Windows 10 Pro, XAMPP, Docker Desktop.
- Execução: 2026-08-04.
- **Diferente das validações anteriores desta Release (5A/5B) e de 4A**: o serviço `mariadb` do `docker/docker-compose.yml` do projeto foi subido de fato nesta rodada (`docker compose up -d mariadb`, porta `13306`), com os bancos de teste `sigma_identity_test`/`sigma_memory_test`/`sigma_mission_test` criados manualmente. Toda a suíte do monorepo rodou contra infraestrutura real, não fakes/skips.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12`.
- Mesmo gap já aceito conscientemente pelo Product Owner desde a Release 2.

## Docker

MariaDB real (`mariadb:10.11`, container `docker-mariadb-1`), saudável (`healthcheck` OK) e usado de fato pelos testes de `Infrastructure/`, não só subido e ignorado. Nenhum outro serviço do `docker-compose.yml` foi necessário nesta Release (sem Redis — ver "Diferença em relação à Release 4B" na Proposal).

## HTTP

**Não aplicável a esta Release.** Sem API HTTP pública para o Mission Engine (mesma decisão já tomada para o Memory Engine em 4B) — Scenario Validation prova persistência via `MissionRepository` direto.

## Testes

| Pacote | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/mission-engine` | `composer test` (PHPUnit) | 51 | 161 |

**Detalhamento**: 37 testes de `Domain/` (Release 5B, inalterados), 9 de `Application/` (fakes), 5 de `Infrastructure/` (MariaDB real via `docker-mariadb-1:13306`).

Suíte completa do monorepo re-executada junto, desta vez **com MariaDB real disponível para todos os pacotes que a exigem**:

| Pacote/Serviço | Testes | Assertions | Skipped |
|---|---|---|---|
| `packages/core` | 8 | 13 | 0 |
| `packages/kernel` | 36 | 78 | 0 |
| `services/event-bus` | 10 | 17 | 0 |
| `services/gateway` | 8 | 22 | 0 |
| `packages/identity-engine` | 72 | 119 | 0 |
| `services/auth` | 5 | 14 | 0 |
| `packages/memory-engine` | 55 | 157 | 0 |
| `services/memory-worker` | 1 | 2 | 0 |
| `packages/mission-engine` | 51 | 161 | 0 |

**Total do monorepo**: 246 testes, 583 assertions, **0 pulados** — a primeira vez neste projeto que a suíte completa roda sem nenhum `Skipped` (as Releases 4A/4B/4.5/5A/5B tiveram entre 15 e 21 testes pulados por falta de MariaDB/Redis de pé na sessão de validação).

## Coverage

Não medida — mesma pendência já registrada desde a Release 2.

## Scenario Validation

- ✅ `MigrationRunner` aplica as seis tabelas em um banco vazio; rodar duas vezes não falha (idempotência). (`PdoMissionRepositoryTest::test_migrations_apply_clean_and_running_twice_does_not_fail`)
- ✅ Uma `Mission` recém-criada (sem Subtask nenhuma ainda) sobrevive a um round-trip completo — `tenantId`/`workspaceId`/`intentId` nulos tratados corretamente. (`test_a_freshly_created_mission_survives_a_round_trip`)
- ✅ `find()` retorna `null` para um `MissionId` inexistente. (`test_find_returns_null_for_an_unknown_id`)
- ✅ **Cenário completo dos quatro fluxos, com múltiplos round-trips intermediários** (persistir → recarregar → transicionar → persistir de novo, repetido a cada passo, não só no final): `Plan` de duas Subtasks, primeira exigindo aprovação (`autonomyCeiling` insuficiente) → `PendingApproval` persistido e recarregado → aprovada (`decidedBy`/`decidedAt` persistidos) → `InProgress` → duas tentativas de retry persistidas na ordem certa (`attempt_number` 1 e 2, com `reason` preservado) → falha definitiva com efeito colateral → `Compensating` persistido → compensada com sucesso → `Failed` final, `Compensation.action` preservado, `Subtask` correspondente `Compensated`. Histórico completo (`Created`→`PendingApproval`→`InProgress`→`Compensating`→`Failed`) recarregado na ordem cronológica certa. (`test_a_mission_with_approval_gate_retry_compensation_and_history_survives_a_full_round_trip`)
- ✅ `Plan`/`SubtaskCandidate` (incluindo `requiredAutonomyLevel` e campos opcionais nulos) e `Subtask.result` (estrutura arbitrária: booleano, decimal, lista aninhada) sobrevivem ao round-trip via coluna `JSON`. `Actor` (`type`+`id`) também. (`test_a_subtask_result_and_plan_survive_the_round_trip_through_json_columns`)
- ✅ Os quinze casos de uso de `Application/` publicam exatamente os eventos esperados, na ordem certa, incluindo o caminho feliz completo (create→advance→assign→start→retry→validate→beginValidation [sem evento]→passValidation), o fluxo de aprovação/rejeição, falha+compensação, e falha de validação. (`MissionApplicationFlowTest` — 9 testes)
- ✅ Cada caso de uso de transição lança `SigmaException('mission.not_found')` para um `MissionId` inexistente; `GetMission` retorna `null` em vez de lançar (consulta, não transição). (`test_mutating_a_missing_mission_throws_not_found`, `test_approving_a_missing_mission_throws_not_found`, `test_get_mission_returns_null_when_absent`)

## Architecture Validation

`grep -rn "^use " src/Application` — toda linha resolve para `Domain/`, a própria interface `MissionRepository`, `Sigma\Kernel\Contract\IEventBus` ou `Sigma\Core\SigmaException`; nenhum import de `Infrastructure/`. `Interfaces/MissionEngineModule.php` importa `Infrastructure/`/`Application/` (esperado, é seu papel) e contratos do Kernel, nunca outro Engine. `composer.lock` não lista `sigma/planner-engine` nem qualquer outro Engine.

## Pendências

- Coverage de código não medido.
- `Identifier` permanece duplicada em três pacotes (`identity-engine`/`memory-engine`/`mission-engine`) — consolidação em `packages/core` recomendada, não decidida.
- Sem consumidor real dos treze eventos publicados — mesma disciplina já aceita em 4B (Memory Engine também não tinha consumidor real de vários eventos).
- Sem API HTTP pública, sem checagem de Permission (`mission.approve`/`mission.reject`/`mission.cancel`), sem política de retry/timeout automática — todas fora de escopo desta Release, ver Proposal.
