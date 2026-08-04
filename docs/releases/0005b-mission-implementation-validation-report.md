# Release 5B — Mission Implementation — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0005b-mission-implementation.md](0005b-mission-implementation.md) (revisão 1).

## Release

Release 5B — Mission Implementation.

## Ambiente

- Windows 10 Pro, XAMPP.
- Execução: 2026-08-04.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12`.
- Mesmo gap já aceito conscientemente pelo Product Owner desde a Release 2.

## Docker

**Não aplicável a esta sub-Release.** Mission Engine 5B é domínio puro — `packages/mission-engine/composer.json` não declara nenhuma dependência de infraestrutura. Nada para subir via Docker.

## HTTP

**Não aplicável a esta sub-Release.** Não existe `Interfaces/` nem endpoint algum para o Mission Engine ainda.

## Testes

| Pacote | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/mission-engine` | `composer test` (PHPUnit) | 37 | 103 |

**Total (mission-engine)**: 37 testes, todos passando, sem nenhum warning/deprecation.

Suíte completa do monorepo re-executada junto, para confirmar que a Release 5B não quebrou nada das Releases anteriores:

| Pacote/Serviço | Testes | Assertions | Skipped |
|---|---|---|---|
| `packages/core` | 8 | 13 | 0 |
| `packages/kernel` | 36 | 78 | 0 |
| `services/event-bus` | 10 | 17 | 0 |
| `services/gateway` | 8 | 22 | 0 |
| `packages/identity-engine` | 72 | 96 | 10 |
| `services/auth` | 5 | 0 | 5 |
| `packages/memory-engine` | 55 | 140 | 5 |
| `services/memory-worker` | 1 | 0 | 1 |
| `packages/mission-engine` | 37 | 103 | 0 |

**Total do monorepo**: 232 testes, todos passando (os 21 `Skipped` em `identity-engine`/`services/auth`/`memory-engine`/`memory-worker` exigem infraestrutura real — Docker/MariaDB/Redis não estavam de pé nesta sessão de validação; pendência pré-existente, não introduzida por esta Release).

## Coverage

Não medida — mesma pendência já registrada desde a Release 2.

## Scenario Validation

Cenários cobertos por teste automatizado (Mission Engine 5B é domínio puro, sem infraestrutura para validar via HTTP real):

- ✅ `Mission::create()` inicia em `Created`, publica `MissionCreated`, rejeita `objective` vazio e `autonomyCeiling` fora de `[0, 3]`. (`MissionTest::test_create_starts_in_created_status_and_records_mission_created`, `test_create_rejects_empty_objective`, `test_create_rejects_autonomy_ceiling_out_of_range`)
- ✅ `workspaceId`/`intentId` são opcionais (ADR-0093). (`MissionTest::test_workspace_and_intent_are_optional`)
- ✅ Avançar para a próxima Subtask com `autonomyCeiling` suficiente cria a Subtask e inicia a Mission (`InProgress`, `MissionStarted`); insuficiente pede aprovação (`PendingApproval`, `MissionApprovalRequested`); uma segunda Subtask em `InProgress` não republica `MissionStarted`. (`MissionTest::test_advancing_with_sufficient_autonomy_starts_the_mission`, `test_advancing_with_insufficient_autonomy_requests_approval`, `test_advancing_a_second_subtask_while_in_progress_does_not_publish_mission_started_again`)
- ✅ Avançar além do fim do `Plan`, ou avançar durante `PendingApproval`, lança exceção. (`MissionTest::test_advancing_beyond_the_plan_throws`, `test_advancing_a_pending_approval_mission_throws`)
- ✅ Aprovar resolve o gate e retoma (`InProgress`, `MissionApproved`); rejeitar cancela (`Cancelled`, `MissionRejected`); aprovar sem gate pendente lança exceção. (`MissionTest::test_approve_resolves_the_gate_and_resumes`, `test_reject_cancels_the_mission`, `test_approve_without_a_pending_gate_throws`)
- ✅ Caminho feliz completo de uma Subtask — `Pending → Assigned → Executing → Validated`, com um `retry` intermediário registrando `RetryAttempt`/`SubtaskRetried` sem sair de `InProgress`. (`MissionTest::test_full_execution_path_to_validated`, `SubtaskTest::test_happy_path_assign_execute_validate`, `test_retry_keeps_executing_and_accumulates_history`)
- ✅ Falha de Subtask **sem** efeito colateral vai direto a `Failed` (`MissionFailed`) — achado real desta Implementation (ver Decision Log). Falha **com** efeito colateral entra em `Compensating` (`MissionCompensationStarted`). (`MissionTest::test_fail_subtask_without_produced_effect_goes_straight_to_failed`, `test_fail_subtask_with_produced_effect_starts_compensation`)
- ✅ Compensar uma Subtask sempre termina a Mission em `Failed` — tanto quando a compensação em si é bem-sucedida (`Compensated`) quanto quando falha (`CompensationFailed`); nunca `Completed` (ADR-0091). (`MissionTest::test_compensating_a_subtask_always_finishes_in_failed_even_when_compensation_succeeds`, `test_compensating_a_subtask_also_finishes_in_failed_when_compensation_itself_fails`)
- ✅ Validação final exige todas as Subtasks `Validated`; aprovada conclui a Mission (`Completed`, `MissionFinished`); reprovada sem efeito vai a `Failed`; reprovada com efeito entra em `Compensating`, mesmo a Subtask apontada já estando `Validated` (achado de modelagem novo, ver Decision Log). (`MissionTest::test_begin_validation_requires_every_subtask_validated`, `test_full_happy_path_to_completed`, `test_fail_validation_without_effect_goes_to_failed`, `test_fail_validation_with_effect_starts_compensation_even_though_subtask_was_validated`)
- ✅ `Subtask::compensate()` aceita origem `Failed` ou `Validated` — nunca `Pending`/`Assigned`/`Executing`. (`SubtaskTest::test_compensate_is_allowed_from_failed`, `test_compensate_is_also_allowed_from_validated`, `test_compensate_is_not_allowed_from_pending`)
- ✅ Cancelamento funciona a partir de `Created` (e, pela mesma guarda, de `PendingApproval`/`InProgress`/`Compensating`/`Validating`); nunca a partir de um estado terminal. (`MissionTest::test_cancel_from_created`, `test_cancel_from_a_terminal_status_throws`)
- ✅ `findSubtask()` lança exceção para um `SubtaskId` inexistente na Mission. (`MissionTest::test_find_subtask_throws_when_absent`)
- ✅ `ApprovalGate` nasce `Pending`, aprova/rejeita uma única vez, uma segunda decisão sobre o mesmo gate lança exceção. (`ApprovalGateTest` — 4 testes)
- ✅ `Mission.history` acumula cada transição de estado real, na ordem em que ocorreram, nunca reescrita. (`MissionTest::test_history_accumulates_every_transition`)
- ✅ Cada um dos treze eventos de [MISSION_EVENTS.md](../../MISSION_EVENTS.md) é produzido pela transição de domínio correta — coberto indiretamente por todo teste de `MissionTest` que inspeciona `pullDomainEvents()`.

**Achado real durante a implementação**: `MISSION_LIFECYCLE.md` já descrevia "Subtask falha sem produzir efeito → Mission vai direto a `Failed`", mas nenhum evento havia sido catalogado para essa transição — corrigido catalogando `MissionFailed` em `MISSION_EVENTS.md`/`EVENT_MODEL.md`/`EVENT_CATALOG.md`/`contracts/Mission.contract.yaml` antes de escrever a classe de evento correspondente (ver Decision Log para o raciocínio completo, mesma disciplina de `MemoryReactivated` na Release 4A).

## Architecture Validation

`grep -rn "^use " packages/mission-engine/src/Domain` — toda linha de import resolve para `Sigma\Core\*` (`Id`, `SigmaException`) ou para o próprio namespace `Sigma\MissionEngine\Domain\*` (incluindo `Domain\Event\*`). Nenhum import de `planner-engine`, `agent-engine`, `skill-engine`, `execution-engine`, `identity-engine` ou `memory-engine`. `composer.lock` do pacote não lista `sigma/planner-engine` nem qualquer outro Engine. Confirma [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md) na prática, não só em prosa.

## Pendências

- Coverage de código não medido.
- `Identifier` permanece duplicada pela terceira vez (`identity-engine`, `memory-engine`, `mission-engine`) — consolidação em `packages/core` recomendada, não decidida.
- `Application`/`Infrastructure`/`Interfaces` do Mission Engine — sem Release nomeada ainda, ver Decision Log.
- 21 testes `Skipped` em `identity-engine`/`services/auth`/`memory-engine`/`memory-worker` (infraestrutura indisponível nesta sessão) — pendência pré-existente, não desta Release.
