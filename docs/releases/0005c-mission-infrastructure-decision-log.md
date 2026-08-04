# Release 5C — Mission Infrastructure — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0005c-mission-infrastructure.md](0005c-mission-infrastructure.md). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

`packages/mission-engine/src/Application/` (interface `MissionRepository` + quinze casos de uso — catorze de transição, um de consulta), `Infrastructure/` (`PdoMissionRepository`, seis tabelas, `MigrationRunner`), `Interfaces/MissionEngineModule`. `composer.json` ganhou `sigma/kernel`/`ext-pdo`. 14 testes novos (9 Application com fake repository, 5 Infrastructure contra MariaDB real) — 51 testes no pacote, 100% verde.

## Achado real: `subtask_retry_attempts` é uma sexta tabela, não prevista na Proposal

A Proposal 5C listava cinco tabelas (`missions`/`subtasks`/`approval_gates`/`compensations`/`mission_history`), espelhando as coleções do próprio `Mission` — mas `Subtask.retryAttempts` é, ela mesma, uma lista aninhada que cresce independentemente (mesmo formato de `Mission.history`), e não tinha sido contada como uma coleção própria. Corrigido com uma sexta tabela, chave primária composta `(subtask_id, attempt_number)` — `attempt_number` já é um valor sequencial estável por Subtask (`Subtask::retry()` sempre incrementa `count($this->retryAttempts) + 1`), então upsert por essa chave funciona sem precisar de um identificador sintético, diferente de `compensations`/`mission_history` (ver abaixo).

## Achado real: `Subtask`/`ApprovalGate` precisaram de `reconstitute()` próprio

A Release 5B só implementou `Mission::reconstitute()` — suficiente para o Domain puro, mas insuficiente para a Infrastructure hidratar as entidades aninhadas a partir de linhas de banco sem violar suas próprias máquinas de estado (ex: um `Subtask` com `status: Compensated` não pode ser reconstruído chamando `assign()`→`startExecution()`→...→`compensate()` em sequência — precisa nascer já naquele estado). Adicionados `Subtask::reconstitute()` e `ApprovalGate::reconstitute()` em `Domain/` (mesmo princípio do `Mission::reconstitute()` já existente: hidrata sem disparar evento algum). É uma adição a `Domain/` feita durante uma Release de Infrastructure — aceitável porque só o próprio `Domain/` pode construir uma entidade válida contornando os métodos públicos de transição; `Infrastructure/` nunca constrói um `Subtask`/`ApprovalGate` diretamente.

## `compensations`/`mission_history` são substituídas por completo (`DELETE` + `INSERT`) a cada `save()`, não upsertadas

Diferente de `missions`/`subtasks`/`approval_gates`/`subtask_retry_attempts` (todas com uma chave estável — `Identifier` próprio ou, no caso dos retries, `attempt_number` sequencial), `Compensation`/`MissionHistoryEntry` não têm identidade própria no Domain (por design — são registros de fato, não Aggregates/Entities). A tabela usa uma chave substituta `AUTO_INCREMENT`, desconhecida do lado do Domain. Diante disso, `save()` apaga e reinsere a lista inteira a cada chamada — mais simples que rastrear "quais linhas já existem" com uma chave que o próprio objeto de domínio não carrega, e correto dado que as duas listas são pequenas (uma Mission não acumula mais que algumas dezenas de transições/compensações). Refina a frase "upsert completo do aggregate" da Proposal — que segue válida para `missions`/`subtasks`/`approval_gates`/`subtask_retry_attempts`, só não descrevia esse caso.

## `missions.pending_approval_gate_id` não tem `FOREIGN KEY`

`missions` referencia `approval_gates` (via `pending_approval_gate_id`) e `approval_gates` referencia `missions` (via `mission_id`) — uma dependência circular entre as duas tabelas. Declarar as duas `FOREIGN KEY` tornaria a ordem de escrita impossível (nenhuma das duas poderia ser inserida primeiro). Resolvido: só `approval_gates.mission_id → missions.id` tem `FOREIGN KEY`; `missions.pending_approval_gate_id` é uma coluna simples, sua integridade garantida pela ordem de escrita em `PdoMissionRepository::save()` (`missions` sempre gravada antes de `approval_gates`), não pelo schema.

## `Plan` continua em `JSON`, exatamente como a Proposal previa — validado na prática

O round-trip completo (`Plan`→JSON→`Plan`) foi testado com dois `SubtaskCandidate`s, incluindo `requiredAutonomyLevel` e campos nulos (`candidateAgent`/`candidateCapability`), confirmando que a decisão de não normalizar não perde nenhuma informação.

## `GetMission` retorna `?Mission`, não lança exceção quando ausente

Diferente dos catorze casos de uso de transição (todos lançam `SigmaException('mission.not_found')` quando o `MissionId` não existe, porque uma transição sobre algo inexistente é sempre um erro de quem chama), uma consulta que não encontra nada é um resultado válido do dia a dia — mesmo padrão de `MissionRepository::find()`/`ContextMemoryRepository::find()` do Memory Engine, só exposto um nível acima.

## Suíte completa do monorepo validada contra MariaDB real, não só contra fakes — achado positivo desta rodada

Diferente das Releases 4A/4B/5A/5B (cujos Validation Reports registram testes `Skipped` por falta de MariaDB/Docker de pé na sessão), esta rodada subiu o serviço `mariadb` do próprio `docker-compose.yml` do projeto e criou os três bancos de teste (`sigma_identity_test`, `sigma_memory_test`, `sigma_mission_test`). Resultado: **0 testes pulados em todo o monorepo** — a primeira vez que isso acontece neste projeto. Ver Validation Report.

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 51 testes no pacote (37 Domain + 9 Application + 5 Infrastructure), `composer test` — todos passando (161 assertions). Suíte completa do monorepo: 246 testes, 583 assertions, 0 skips.
2. **Architecture Validation**: `Application/` não importa nada de `Infrastructure/` (só a interface `MissionRepository` que ela mesma declara); `Interfaces/MissionEngineModule` só importa PDO para a própria conexão (mesmo padrão de `MemoryEngineModule`), nunca de dentro de `Application/`; nenhum arquivo em `Application/`/`Infrastructure/`/`Interfaces/` importa `planner-engine`/`agent-engine`/`skill-engine`/`execution-engine`/`identity-engine`/`memory-engine`; `composer.lock` não lista nenhum desses pacotes.
3. **Scenario Validation**: ver [Validation Report](0005c-mission-infrastructure-validation-report.md).

## Impacto para releases futuras

- `system-manifest.yaml` ganhou o Module `mission-engine` — `optional: true`, sem nenhum service ainda registrando-o de fato (diferente de `identity-engine`/`memory-engine`, sinalizado explicitamente no próprio Manifest).
- Quando o Planner Engine (Release 6) existir e publicar `MissionPlanned`, um consumidor real (worker ou handler síncrono) passa a fazer sentido para o Mission Engine — não implementado agora, mesma disciplina de não antecipar Releases futuras.
- `Identifier` permanece duplicada pela terceira vez (sem mudança nesta Release, só reafirmando a pendência já registrada em 4A/5B).
- O `docker-compose` do projeto (serviço `mariadb`) ficou de pé ao final desta sessão, com os três bancos de teste já criados — útil para a próxima rodada de validação não precisar repetir esse setup.
