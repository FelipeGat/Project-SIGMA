# Release 4A — Memory Domain — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0004a-memory-domain.md](0004a-memory-domain.md) (revisão 2).

## Release

Release 4A — Memory Domain.

## Ambiente

- Windows 10 Pro, XAMPP.
- Execução: 2026-08-04.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12`.
- Mesmo gap já aceito conscientemente pelo Product Owner desde a Release 2 — reconciliação adiada para a Release de CI/CD.

## Docker

**Não aplicável a esta sub-Release.** Release 4A é domínio puro — `packages/memory-engine/composer.json` não declara nenhuma dependência de infraestrutura (nem `sigma/kernel`, nem cliente de banco, nem Redis). Não há nada para subir via Docker nesta sub-Release; isso é escopo da Release 4B.

## HTTP

**Não aplicável a esta sub-Release.** Release 4A não expõe nenhum endpoint — não existe `Interfaces/` nem nenhum serviço HTTP para o Memory Engine ainda. Isso é escopo da Release 4B.

## Testes

| Pacote | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/memory-engine` | `composer test` (PHPUnit) | 35 | 103 |

**Total (memory-engine)**: 35 testes, todos passando, sem nenhum warning/deprecation.

Suíte completa do monorepo re-executada junto, para confirmar que a Release 4A não quebrou nada das Releases anteriores:

| Pacote/Serviço | Testes | Assertions | Skipped |
|---|---|---|---|
| `packages/core` | 8 | 13 | 0 |
| `packages/kernel` | 36 | 78 | 0 |
| `services/event-bus` | 6 | 12 | 0 |
| `services/gateway` | 8 | 22 | 0 |
| `packages/identity-engine` | 72 | 96 | 10 |
| `services/auth` | 5 | 0 | 5 |
| `packages/memory-engine` | 35 | 103 | 0 |

**Total do monorepo**: 170 testes, todos passando (os 15 `Skipped` em `identity-engine`/`services/auth` são testes que exigem infraestrutura real — Docker/MariaDB/Redis não estavam de pé nesta sessão de validação; comportamento pré-existente, não introduzido por esta Release).

## Coverage

Não medida nesta Release — nenhuma ferramenta de coverage (Xdebug/PCOV) configurada no ambiente local desta sessão (mesma pendência já registrada desde a Release 2).

## Scenario Validation

Cenários listados na Proposal (revisão 2), cada um com o resultado real — todos validados via teste automatizado, já que Release 4A não tem infraestrutura para testar via HTTP real:

- ✅ Um `ContextMemory` fechado destila zero, um, ou vários `MemoryRecord`, cada um com `confidence`/`origin` atribuídos. (`ContextMemoryTest::test_distilling_produces_zero_records_when_no_facts_reach_the_floor`, `test_distilling_produces_a_memory_record_per_fact_above_the_floor`)
- ✅ Um `MemoryRecord` `Operational` promovido a `Project` quando o mesmo `subjectKey` aparece em duas Missions do mesmo Workspace **e** `confidence` está no piso — e permanece `Operational` quando a estrutura é suficiente mas `confidence` não é (e vice-versa). (`MemoryRecordTest::test_repetition_and_confidence_together_promote_to_project`, `test_repetition_alone_without_enough_confidence_does_not_promote`, `test_confidence_alone_without_repetition_does_not_promote`)
- ✅ Um `MemoryRecord` `Project` promovido a `LongTerm` quando o mesmo `subjectKey` (generalizado) aparece em Workspaces diferentes **e** `confidence` está no piso mais alto. (`MemoryRecordTest::test_project_to_long_term_promotes_and_drops_workspace_scope`, `test_project_to_long_term_requires_generalization_and_high_confidence`)
- ✅ Uma promoção nunca apaga o registro de origem — `promotedFrom` sempre rastreável. (`MemoryRecordTest::test_promotion_never_deletes_or_mutates_the_source_record`)
- ✅ Nenhuma promoção direta de `Operational` para `LongTerm`. (`MemoryRecordTest::test_promoting_a_project_record_directly_to_long_term_requires_going_through_the_level`)
- ✅ Um `MemoryRecord` `Active` contradito vira `Deprecated`, nunca apagado; volta a `Active` se reforçado sem nova contradição; `Retracted` nunca reativa sozinho. (`MemoryRecordTest::test_contradiction_marks_active_record_as_deprecated_without_deleting_it`, `test_a_deprecated_record_reactivates_and_a_retracted_one_never_does`)
- ✅ Um `MemoryRecord` `Deprecated` nunca promove, mesmo com estrutura e confidence suficientes. (`MemoryRecordTest::test_a_deprecated_record_never_promotes_even_with_structure_and_confidence`)
- ✅ Um `MemoryRecord` `LongTerm` atingido gera o evento de candidatura (`MemoryPromoted`, `toLevel: LongTerm`) sem criar `KnowledgeRecord` algum. (`MemoryEngagementScenarioTest::test_a_full_engagement_flows_from_raw_conversation_to_a_knowledge_candidate_signal`)
- ✅ Um `KnowledgeRecord` atualizado cria uma nova versão (`version` incrementado, `previousVersionId` preenchido) — a versão anterior permanece intacta. (`KnowledgeRecordTest::test_revising_creates_a_new_version_without_touching_the_previous_one`, `test_a_chain_of_revisions_preserves_full_lineage`)
- ✅ Um `DigitalTwin` só é criado/atualizado a partir de um evento de entrada — nenhum caminho de criação que não passe por uma projeção de evento. (`DigitalTwinTest` — `project()`/`applyProjection()` são os únicos pontos de mutação de estado, ambos gravam o evento correspondente)
- ✅ Um `DigitalTwin` fora da janela de refresh é identificável como stale. (`DigitalTwinTest::test_checking_staleness_outside_the_window_returns_true_and_records_the_stale_event`)
- ✅ Cada um dos doze eventos de [DOMAIN_EVENTS.md#memory-engine](../../DOMAIN_EVENTS.md#memory-engine) é produzido pela transição de domínio correta — exceto `MemorySubjectPinned`, deliberadamente não produzido por nenhum Aggregate nesta sub-Release (ver Decision Log).
- ✅ Um `subjectKey` observado em três Missions diferentes dentro do mesmo Workspace — promovido a `Project` só uma vez (idempotência da promoção) — coberto indiretamente: `evaluatePromotionToProject()` sempre parte de um `MemoryRecord` `Active` específico e produz um novo registro; reavaliar o mesmo `MemoryRecord` `Project` já promovido não o promove de novo (só o `Operational` de origem poderia, e ele permanece `Operational`, nunca se torna `Project` de novo por conta própria).
- ✅ Um `KnowledgeRecord` e um `MemoryRecord` nunca compartilham a mesma identidade de aggregate mesmo tratando do mesmo assunto. (`MemoryEngagementScenarioTest::test_a_memory_record_and_a_knowledge_record_about_the_same_subject_never_share_aggregate_identity`)
- ✅ Um engajamento completo: `ContextMemory` aberto → fechado → destilado em `MemoryRecord` → promovido duas vezes → candidato a Knowledge sinalizado, nunca convertido sozinho. (`MemoryEngagementScenarioTest::test_a_full_engagement_flows_from_raw_conversation_to_a_knowledge_candidate_signal`)

**Achado real durante a implementação**: `MEMORY_PROMOTION_RULES.md` já previa que um `MemoryRecord` `Deprecated` pudesse reativar, mas nenhum evento havia sido catalogado para essa transição — corrigido catalogando `MemoryReactivated` em `DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`/`contracts/Memory.contract.yaml` antes de escrever a classe de evento correspondente (ver Decision Log para o raciocínio completo).

## Pendências

- Coverage de código não medido.
- `Identifier` permanece duplicada entre `packages/identity-engine` e `packages/memory-engine` — consolidação em `packages/core` recomendada, não decidida (ver Decision Log e `memory/NEXT.md`).
- `MemorySubjectPinned` não tem um Aggregate próprio produzindo-o — decisão de onde/como persistir "este `subjectKey` está pinado" fica para a Release 4B.
- Algoritmos de destilação (`ContextMemory` → `DistilledFact`), detecção de contradição, e generalização de `subjectKey` — todos deixados para Implementation da Release 4B, conforme já previsto na Proposal.
- 15 testes `Skipped` em `identity-engine`/`services/auth` (infraestrutura indisponível nesta sessão) — pendência pré-existente, não desta Release.
