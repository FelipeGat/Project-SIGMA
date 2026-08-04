# Release 5C — Mission Infrastructure

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md) e o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) ([ADR-0082](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)). **Revisão 1 — aguardando aprovação do Product Owner.** Terceira sub-Release da Release 5 — Mission Engine: [5A — Research](0005a-mission-research.md) (modelagem, sem código) e [5B — Domain](0005b-mission-implementation.md) (o aggregate `Mission` completo, sem infraestrutura) já estão implementadas e validadas; esta cobre `Application/`, `Infrastructure/`, `Interfaces/`.

## Nota sobre numeração

A Proposal 5B já declarava explicitamente (Decision Log) que não se subdividiria mais, deixando `Application`/`Infrastructure`/`Interfaces` "para uma Release futura, ainda sem número". Esta Proposal fecha essa lacuna nomeando-a **5C**, em vez de abrir um número de Release novo — mesmo espírito de [ADR-0060](../adr/0060-release-dividida-em-sub-releases.md) (uma Release complexa pode se dividir em quantas sub-Releases sequenciais o risco real exigir; a divisão em duas, usada por Identity/Memory, não é um teto). Mission acabou precisando de três sub-Releases (Research/Domain/Infrastructure) em vez de duas (Domain/Infrastructure) porque, diferente de Identity/Memory, o Product Owner pediu explicitamente uma fase de pesquisa dedicada antes até do Model (ver [0005a-mission-research.md](0005a-mission-research.md)) — a divisão em três reflete esse pedido, não um novo padrão geral.

**Diferente da Release 5B, esta Proposal está sendo apresentada para aprovação explícita antes de qualquer código** — não presume que a aprovação de 5A/5B já a cobre, já que o escopo de Infrastructure não estava detalhado em nenhuma das duas.

## Objetivo

Dar alcance real ao aggregate `Mission` já modelado e testado em 5B: persistir `Mission` (com `Subtask`/`ApprovalGate`/`Compensation`/`MissionHistoryEntry` aninhados) em MariaDB, publicar de fato os treze eventos de domínio no `IEventBus` depois de cada transição, e registrar `MissionEngineModule` no Kernel — mesmo objetivo e disciplina de 3B/4B, adaptado ao que muda para Mission (ver "Diferença em relação a 4B" abaixo).

## Diferença em relação à Release 4B — Memory Infrastructure

A Release 4B teve como item central um listener Redis cross-processo real (`services/memory-worker`), porque o Memory Engine precisava **consumir** eventos publicados por outro processo (`services/auth`) para sincronizar `UserTwin`. **Mission Engine não tem essa necessidade nesta Release**: nenhum Engine anterior publica eventos que o Mission Engine precise consumir — Planner/Intent Engine (Releases 6/7) ainda não existem (ADR-0031), e é justamente `MissionPlanned` (publicado pelo Planner) que futuramente viraria a entrada real de uma Mission. Nesta Release, quem cria/avança uma `Mission` é sempre um caller direto (teste, ou futuramente Planner/Agent/Execution Engine chamando a Application via Container) — não um worker reagindo a um evento externo. Por isso, **não há `services/mission-worker` nesta Proposal** — os treze eventos de domínio são **publicados** por `Application/` depois de cada transição (para consumidores futuros, como já documentado em [EVENT_CATALOG.md](../../EVENT_CATALOG.md#mission-engine): "Audit Engine, Agent Engine"), mas o Mission Engine não **assina** nenhum evento nesta Release.

## Escopo

**Existe:**

- `packages/mission-engine/src/Application/` — casos de uso sobre o `Domain/` já validado por 5B, sem conhecer MariaDB/Redis diretamente:
  - `CreateMission` (recebe `Plan`/`Actor`/`autonomyCeiling` já resolvidos, chama `Mission::create()`, persiste, publica `MissionCreated`).
  - `AdvanceMissionToNextSubtask`, `ApproveMission`, `RejectMission`, `AssignSubtask`, `StartSubtaskExecution`, `RetrySubtask`, `ValidateSubtask`, `FailSubtask`, `CompensateSubtask`, `BeginMissionValidation`, `PassMissionValidation`, `FailMissionValidation`, `CancelMission` — um caso de uso por método de transição público de `Mission` (mesmo mapeamento 1:1 já usado em 4A→4B), cada um: carrega a `Mission` via repositório, chama o método de `Domain/` correspondente, persiste, publica os eventos pendentes (`pullDomainEvents()`) via `IEventBus`.
  - `GetMission` (consulta, sem side-effect).
- Interface de repositório declarada em `Application/`: `MissionRepository` (`findById(MissionId): ?Mission`, `save(Mission): void`) — implementada por `Infrastructure/`, nunca o contrário.
- `packages/mission-engine/src/Infrastructure/` — `PdoMissionRepository` sobre PDO puro ([ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)), migrations para cinco tabelas: `missions` (`tenant_id` obrigatório, [ADR-0021](../adr/0021-multitenancy-desde-o-schema.md)), `subtasks`, `approval_gates`, `compensations`, `mission_history` — as últimas quatro referenciam `mission_id` via `FOREIGN KEY`, sem `tenant_id` próprio (mesmo padrão já usado por `sessions` no Identity Engine — uma entidade que só existe dentro do limite de outro aggregate não carrega `tenant_id` duplicado). `plan`/`subtaskCandidates` persistidos como uma coluna `JSON` em `missions` (é um Value Object de entrada imutável, nunca consultado por campo próprio — ver "Arquitetura" abaixo).
- `packages/mission-engine/src/Interfaces/MissionEngineModule implements IModule` (`kind: Engine`, `dependsOn: ['kernel', 'event-bus']`), registrando os casos de uso/repositório no Container — mesmo padrão de `MemoryEngineModule`, sem assinar nenhum evento (ver "Diferença em relação à 4B").
- `system-manifest.yaml` ganha o Module `mission-engine`.

**Não existe ainda:**

- **`services/mission-worker`/qualquer listener Redis** — deliberadamente fora de escopo, ver "Diferença em relação à 4B" acima. Se/quando o Planner Engine (Release 6) existir e publicar `MissionPlanned`, um consumidor real (worker ou handler síncrono, decisão de quando o Planner Engine for desenhado) passa a fazer sentido — não antecipado aqui.
- **API HTTP pública para o Mission Engine** (`services/mission`) — mesma decisão já tomada para o Memory Engine em 4B: sem consumidor humano/HTTP real ainda (Agent/Execution Engine, que disparariam `assignSubtask`/`validateSubtask`/etc. de verdade, são Releases 9/10). Scenario Validation prova persistência via repositório direto, não via `curl`.
- **Checagem de Permission** (`mission.approve`/`mission.reject`/`mission.cancel`, já vocabulário em `contracts/Mission.contract.yaml`) — `Application/` não conhece Identity/Context nesta Release, mesma disciplina já usada em 4B para as três Permissions do Memory Engine.
- **Política de retry/backoff automática** — `RetrySubtask` continua sendo um caso de uso disparado por quem chama (teste, ou futuramente Agent/Execution Engine), nunca uma decisão automática do Mission Engine; número de tentativas e backoff continuam não decididos (MISSION_MODEL.md já sinalizava isso como decisão de Implementation futura, não desta Release).
- **Timeout automático de `ApprovalGate` pendente** — mesma razão acima.

**Onde vive:**

- `packages/mission-engine/src/Application/`, `Infrastructure/`, `Interfaces/` — as três camadas que faltavam.

## Arquitetura

`Interfaces/MissionEngineModule` só conhece as interfaces do Kernel API ([ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md)) — nunca PDO diretamente. `Application/` só conhece `Domain/` e sua própria interface de repositório — nunca MariaDB diretamente. `Infrastructure/` implementa essa interface sobre PDO puro.

**`Plan` persistido como `JSON` em `missions`, não normalizado em tabela própria**: `Plan`/`SubtaskCandidate` são Value Objects imutáveis, nunca modificados depois da criação da `Mission` (MISSION_MODEL.md: "nunca modificado depois da criação... é o 'contrato' que a Mission está executando") e nunca consultados por campo próprio (não há caso de uso "buscar Missions cujo Plan tem uma Subtask candidata com tal descrição"). Normalizar em tabela própria replicaria exatamente a estrutura de `subtaskCandidates` sem nenhum ganho de consulta — uma coluna `JSON`, hidratada de volta em `Plan`/`SubtaskCandidate[]` no `PdoMissionRepository`, é a persistência mais simples que ainda preserva o dado fielmente. Mesmo julgamento já usado para `data`/payload de evento em Releases anteriores.

**`Mission.history` (`MissionHistoryEntry[]`) persistido em tabela própria (`mission_history`), não em `JSON`** — diferente do `Plan`, é uma lista que cresce ao longo da vida da Mission (uma linha por transição), não um bloco imutável escrito uma vez; uma tabela própria (`mission_id`, `status`, `at`) é a forma natural, consistente com como `subtasks`/`approval_gates`/`compensations` já são tratados.

**`PdoMissionRepository::save()` é upsert completo do aggregate** — mesmo padrão já usado por Identity/Memory: carrega o estado atual, decide inserir/atualizar cada tabela filha (`subtasks`/`approval_gates`/`compensations`/`mission_history`) comparando com o que já existe, tudo dentro de uma transação PDO — nunca deleta e recria linhas já existentes (preservaria os IDs gerados por `Identifier::generate()` de qualquer forma, mas a transação evita qualquer janela de inconsistência).

**Publicação de eventos acontece em `Application/`, depois de persistir** — mesmo padrão de Identity/Memory: `Mission::pullDomainEvents()` só é chamado depois que `MissionRepository::save()` retorna com sucesso, para nunca publicar um evento de uma mudança que não foi de fato persistida.

## Dependências

- Release 5B — Mission Domain, implementada e validada.
- Release 3.5 (`InMemoryEventBus`/`IEventBus` em `packages/kernel`) — publicação de eventos, sem necessidade do `RedisSubscriber` desta vez (ver "Diferença em relação à 4B").
- MariaDB disponível no ambiente (já existe via `docker-compose.yml` desde a Release 3B) — reaproveitado, schema novo.

## Riscos

1. **Persistir um aggregate com quatro coleções aninhadas (`subtasks`/`approval_gates`/`compensations`/`history`) é mais complexo que qualquer repositório já escrito no projeto** (Identity/Memory não têm um aggregate com múltiplas listas filhas crescendo independentemente). Mitigado por transação PDO única em `save()` e por testes de integração cobrindo especificamente round-trips com Subtasks/gates/compensações/histórico em diferentes tamanhos (zero, um, vários).
2. **`Plan` em `JSON` é uma decisão que reduz consultabilidade** (não dá para `WHERE` num campo de `subtaskCandidates` via SQL simples) — aceito conscientemente porque nenhum caso de uso desta Release precisa disso; revisitar se um caso de uso futuro exigir (ex: Planner Engine consultando Plans por padrão).
3. **Sem consumidor real nesta Release** (mesmo risco já aceito em 4B para várias Permissions/APIs) — os treze eventos publicados não têm nenhum assinante de fato ainda; aceito conscientemente, mesma disciplina de não antecipar Releases futuras.

## Entregáveis

- `packages/mission-engine/src/Application/`, `Infrastructure/`, `Interfaces/` implementados.
- Migrations para as cinco tabelas.
- `system-manifest.yaml` atualizado com o Module `mission-engine`.
- **Decision Log** (`docs/releases/0005c-mission-infrastructure-decision-log.md`).
- **Validation Report** (`docs/releases/0005c-mission-infrastructure-validation-report.md`).

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- `Application/`: testado com uma implementação em memória de `MissionRepository` (test double) — mesmo espírito de 3B/4B — cobrindo cada caso de uso e a publicação de eventos via um `IEventBus` fake.
- `Infrastructure/`: testado contra uma MariaDB real (via `docker-compose`) — round-trip completo do aggregate (`save()` → `findById()` → assert de igualdade estrutural), incluindo Missions com zero, uma e várias Subtasks/gates/compensações/entradas de histórico.
- Migrations aplicam limpo em um banco vazio; rodar duas vezes não falha.

### 2. Architecture Validation
- `Application/` não importa nenhuma classe de `Infrastructure/` (só as interfaces que ela mesma declara).
- `Interfaces/MissionEngineModule` não importa PDO diretamente.
- Nenhuma classe de `Application/`/`Infrastructure/`/`Interfaces/` importa `planner-engine`/`agent-engine`/`skill-engine`/`execution-engine`/`identity-engine`/`memory-engine` — mesma verificação já feita em `Domain/` na 5B, estendida às camadas novas.

### 3. Scenario Validation
- `docker compose up --build` real, `MissionEngineModule` registrado — subida validada.
- Criar uma Mission via `CreateMission`, avançar por todos os quatro fluxos (aprovação, execução com retry, falha com/sem compensação, validação final) via os casos de uso, e confirmar em cada passo que o estado persistido em MariaDB bate com o estado em memória do aggregate.
- Reiniciar o processo (nova conexão PDO) e recarregar a mesma Mission via `findById()` — confirma que `reconstitute()` produz um aggregate equivalente ao que foi persistido, inclusive quanto a `Subtask`s já `Compensated` a partir de origem `Validated` (o caso encontrado na 5B).

## Critérios de Aceite

- As cinco tabelas existem, com `tenant_id` obrigatório em `missions`, migrations aplicadas via `docker-compose up --build` real.
- `MissionEngineModule` sobe via Bootstrap sem exigir mudança no contrato `IModule`.
- Um aggregate `Mission` complexo (múltiplas Subtasks, um `ApprovalGate` decidido, uma `Compensation`, histórico de mais de quatro transições) sobrevive a um round-trip completo de persistência sem perda de dado.
- Os treze eventos de [MISSION_EVENTS.md](../../MISSION_EVENTS.md) são publicados de fato no `IEventBus` pela `Application/` correspondente.
- Os três níveis de validação executados e documentados no Validation Report.
- `link-check`/`adr-check` limpos.
- Aprovação explícita do Product Owner antes de qualquer código de `Application/`/`Infrastructure/`/`Interfaces/` — sem exceção.
