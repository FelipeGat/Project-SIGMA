# Release 5B — Mission Implementation — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0005b-mission-implementation.md](0005b-mission-implementation.md). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

Ver "Escopo" em [0005b-mission-implementation.md](0005b-mission-implementation.md) — lista completa. Este documento cobre só o porquê das decisões tomadas durante a escrita do código, não repetidas ali.

## Achado real durante a Implementation: faltava um evento para "Subtask falha sem produzir efeito"

`MISSION_LIFECYCLE.md` (Fluxo 2, etapa 4) já descrevia essa transição — "se a Subtask falhou definitivamente sem ter produzido nenhum efeito colateral... a Mission pode ir direto a `Failed` também, sem precisar de compensação" — mas só o caminho de compensação tinha evento (`MissionCompensationFinished`). Corrigido **antes** de escrever a classe de evento correspondente: `MISSION_EVENTS.md` (nova seção "Falha sem compensação"), `EVENT_MODEL.md` (linha nova no catálogo canônico), `EVENT_CATALOG.md`, `docs/releases/0005a-mission-research.md` (contagem "doze"→"treze"), `contracts/Mission.contract.yaml` — mesma disciplina já usada para `MemoryReactivated` na Release 4A. `MissionFailed` (`mission.failed`) é o décimo terceiro evento do Mission Engine.

## `advanceToNextSubtask()` substitui o par `addSubtask()`/`evaluateApproval()` inicialmente cogitado

A leitura literal de `MISSION_LIFECYCLE.md` (Fluxo 1 e Fluxo 2) mostra a criação da `Subtask` e a avaliação do gate de autonomia como um único evento de negócio — "puxar a próxima Subtask candidata do Plan" — não duas operações que um chamador externo orquestraria separadamente. Um único método `Mission::advanceToNextSubtask(\DateTimeImmutable $now): Subtask` puxa a próxima `SubtaskCandidate` do `Plan` (por índice — `count($this->subtasks)`), cria a `Subtask` (`Pending`, publica `SubtasksCreated`), e então decide: `requiredAutonomyLevel` insuficiente → `PendingApproval` + `MissionApprovalRequested`; suficiente e a Mission ainda `Created` → `InProgress` + `MissionStarted`; suficiente e já `InProgress` → nenhum evento adicional (idempotente para a segunda Subtask em diante). Mantém a Mission como única responsável por decidir a ordem de suas próprias Subtasks — nenhuma lógica de "qual é a próxima" vaza para fora do Aggregate.

## `completeValidation()` (design original) virou dois métodos: `passValidation()`/`failValidation()`

Mais simples e mais próximo de como `approve()`/`reject()` e `failSubtask()` (bool `$hasProducedEffect`) já foram desenhados — dois desfechos distintos do Fluxo 4, cada um com sua própria assinatura, em vez de um parâmetro `bool $passed` guardando campos que só fazem sentido num dos dois ramos.

## `failValidation()` exige um `SubtaskId $faultingSubtaskId` — decisão de modelagem nova, não coberta literalmente por MISSION_MODEL.md

`MISSION_LIFECYCLE.md` (Fluxo 4) diz que uma validação final reprovada é "tratada como falha de Subtask... mesma lógica do Fluxo 2, etapa 4" — mas o Fluxo 4 só roda depois que **todas** as Subtasks já estão `Validated` (não há uma Subtask "atualmente falhando" para apontar automaticamente). Interpretação adotada: quem chama `failValidation()` (a Application, numa Release futura) já sabe, pela checagem que reprovou a validação agregada, qual Subtask especificamente produziu o efeito problemático — e informa isso explicitamente. `Compensation.subtaskId` (MISSION_MODEL.md) já exige uma Subtask de referência; não havia como evitar essa decisão sem violar essa exigência do próprio modelo. Documentado aqui como achado de modelagem porque `MISSION_MODEL.md` não antecipava esse caso — não contradiz o modelo, só resolve uma ambiguidade que ele deixou aberta.

## `Subtask::compensate()` passou a aceitar origem `Failed` **ou** `Validated`

Consequência direta da decisão acima: quando `failValidation()` aponta uma Subtask como origem de um efeito a compensar, essa Subtask está `Validated` (validou normalmente; só a checagem agregada da Mission é que reprovou), não `Failed`. Forçar essa Subtask a passar por `fail()` antes de `compensate()` seria falso — ela não falhou, seu resultado é que não bastou. `Subtask::compensate()` (`Subtask.php`) agora aceita `Failed` **ou** `Validated` como estado de origem válido, com um comentário explicando a origem dupla. Coberto por `SubtaskTest::test_compensate_is_also_allowed_from_validated` e `MissionTest::test_fail_validation_with_effect_starts_compensation_even_though_subtask_was_validated`.

## `Mission.history` implementado como `list<MissionHistoryEntry>` (`status` + `at`)

`MISSION_MODEL.md` especifica `history` como "lista append-only de transições de estado já ocorridas — nunca editada, só acrescida". Criada `MissionHistoryEntry` (novo Value Object, `status: MissionStatus`, `at: \DateTimeImmutable`) — o menor tipo que satisfaz literalmente essa frase, sem inventar campos que o modelo não pediu (ex: motivo da transição, que já é coberto por `ApprovalGate.reason`/`Compensation.action` quando aplicável). Um `transitionTo()` privado centraliza toda mudança de `status`, garantindo que `history` nunca desincroniza do `status` real — testado em `MissionTest::test_history_accumulates_every_transition`.

## `beginValidation()` valida que toda Subtask está `Validated` e que existe pelo menos uma

`MISSION_LIFECYCLE.md` (Fluxo 4) declara "Todas as Subtasks Validated" como pré-condição em prosa — formalizada como guarda explícita no próprio Aggregate (`SigmaException('mission.subtasks_not_validated')`), em vez de confiar em quem chama para respeitar a pré-condição. A checagem de lista vazia evita o caso degenerado (vacuamente verdadeiro) de validar uma Mission sem nenhuma Subtask ainda criada.

## `compensateSubtask()` sempre finaliza a Mission (`Failed`), nunca deixa `Compensating` como estado de espera

Uma única chamada registra a `Compensation`, aplica `Subtask::compensate()`, publica `SubtaskCompensated`, e imediatamente transiciona `Failed` + publica `MissionCompensationFinished` — reflete literalmente [ADR-0091](../adr/0091-retry-subtask-compensacao-mission.md)/Fluxo 3 ("uma Mission que passou por `Compensating` sempre termina em `Failed`") e a simplificação já assumida na Proposal 5A de que este Release trata só uma Subtask em compensação por vez (modelo de granularidade grossa — múltiplas compensações concorrentes ficam para quando houver um caso real que exija isso).

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 37 testes, `composer test` — todos passando (103 assertions), sem nenhum warning/deprecation.
2. **Architecture Validation**: `grep -rn "^use "` em todo `Domain/` mostra só imports de `Sigma\Core\*` e do próprio namespace `Sigma\MissionEngine\Domain\*` — nenhum import de `planner-engine`/`agent-engine`/`skill-engine`/`execution-engine`/`identity-engine`/`memory-engine`, nenhum `Application`/`Infrastructure`/`Interfaces` (nenhuma dessas pastas existe ainda). `composer.lock` não lista `sigma/planner-engine` nem qualquer outro Engine como dependência. Todo identificador em toda assinatura pública é um `Identifier` concreto, nunca `string`.
3. **Scenario Validation**: ver [Validation Report](0005b-mission-implementation-validation-report.md).

## Impacto para releases futuras

- `Application`/`Infrastructure`/`Interfaces` do Mission Engine ainda não têm Release nomeada — a Proposal 5A já sinalizava isso implicitamente ("Qualquer Application/Infrastructure/Interfaces... fica para a Release 5B"), e esta 5B decidiu não se subdividir mais (ver nota de processo na Proposal). A numeração da próxima Release (5C? uma nova Release inteira?) fica em aberto, sinalizada em `memory/NEXT.md`.
- Quem primeiro dispara `assignSubtask()`/`startSubtaskExecution()`/`validateSubtask()`/`failSubtask()` de fato (hoje só testes) será o Agent/Execution Engine (Releases 9/10) — `Domain/` só valida que a transição é permitida, nunca decide sozinho quando ela acontece (MISSION_MODEL.md, "O que este modelo não decide").
- `Identifier` permanece duplicada pela terceira vez (`identity-engine`, `memory-engine`, agora `mission-engine`) — consolidação em `packages/core` continua recomendada, não decidida, mesma pendência sinalizada desde a Release 4A.
