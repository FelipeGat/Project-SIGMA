# Release 3A — Identity Domain — Validation Report

Prova de execução da fase de Validation ([ADR-0048](../adr/0048-processo-quatro-fases.md), [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Proposta: [0003a-identity-domain.md](0003a-identity-domain.md) (revisão 3).

## Release

Release 3A — Identity Domain.

## Ambiente

- Windows 10 Pro, XAMPP.
- Execução: 2026-08-04.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12`.
- Mesmo gap já aceito conscientemente pelo Product Owner desde a Release 2 — reconciliação adiada para a Release de CI/CD.

## Docker

**Não aplicável a esta sub-Release.** Release 3A é domínio puro — `packages/identity-engine/composer.json` não declara nenhuma dependência de infraestrutura (nem `sigma/kernel`, nem cliente de banco, nem Redis). Não há nada para subir via Docker nesta sub-Release; isso é escopo da Release 3B.

## HTTP

**Não aplicável a esta sub-Release.** Release 3A não expõe nenhum endpoint — não existe `Interface/` nem `services/auth` ainda. Isso é escopo da Release 3B.

## Testes

| Pacote | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/identity-engine` | `composer test` (PHPUnit) | 50 | 79 |

**Total**: 50 testes, todos passando. Suíte completa do monorepo re-executada junto (core 4, kernel 36, event-bus 6, gateway 8, identity-engine 50) — **104 testes, todos passando**, confirmando que a Release 3A não quebrou nada da Release 2.

## Coverage

Não medida nesta Release — nenhuma ferramenta de coverage (Xdebug/PCOV) configurada no ambiente local desta sessão (mesma pendência já registrada desde a Release 2).

## Scenario Validation

Cenários listados na Proposal (revisão 3), cada um com o resultado real — todos validados via teste automatizado, já que Release 3A não tem infraestrutura para testar via HTTP real:

- ✅ Um `Identity` autenticado, Workspace selecionado, com Role atribuído só via Team — Permissions/Autonomy resolvidas corretamente, tudo em memória, sem banco. (`IdentityTest::test_a_role_received_only_via_team_membership_still_resolves`)
- ✅ Trocar de Workspace produz uma nova Session/Context — o Context anterior permanece inalterado. (`SessionTest::test_with_workspace_selected_returns_a_new_instance_without_mutating_the_original`, `SessionTest::test_selecting_a_workspace_a_second_time_is_rejected`)
- ✅ Uma tentativa de autenticar uma Identity desativada é rejeitada pelo próprio agregado. (`IdentityTest::test_an_inactive_identity_cannot_authenticate`, `test_a_disabled_identity_cannot_authenticate`)
- ✅ Um assignment revogado não se aplica. (`IdentityTest::test_a_revoked_assignment_does_not_apply`)
- ✅ Um assignment escopado a um Workspace diferente não se aplica; um assignment escopado ao Tenant se aplica a qualquer Workspace dele. (`IdentityTest::test_an_assignment_scoped_to_a_different_workspace_does_not_apply`, `test_an_assignment_scoped_to_the_tenant_applies_to_any_workspace_within_it`)
- ✅ Uma Session de uma Identity nunca é aceita para operações de outra. (`IdentityTest::test_a_session_from_a_different_identity_is_rejected`)
- ✅ Sequência completa de eventos de domínio (`IdentityCreated` → `IdentityActivated` → `SessionStarted` → `WorkspaceSelected`) na ordem correta. (`IdentityTest::test_the_full_lifecycle_produces_the_expected_sequence_of_domain_events`)

**Achado real durante a implementação**: a suíte inicial usava `@dataProvider` em doc-comment (`PermissionTest`) — PHPUnit 11 emitiu deprecation ("suporte removido no PHPUnit 12"). Corrigido para o atributo `#[DataProvider(...)]` antes de considerar a suíte limpa; a suíte só foi aceita como "100% verde" depois de rodar sem nenhum warning/deprecation, não só sem falha.

## Pendências

- Coverage de código não medido.
- A divergência entre `autonomy_level_required` (inteiro 0-3, Sigma Contracts/infraestrutura) e `autonomyCapabilities` (mapa nomeado, Identity Engine — [ADR-0068](../adr/0068-autonomy-por-capability.md)) não está reconciliada — sinalizada para o Skill Engine (Release 8), não bloqueia a Release 3B.
- `IDENTITY_MODEL.md` ainda descreve `Context` como o objeto de mais alto nível, não `Identity` — divergência do texto original documentada em [ADR-0064](../adr/0064-identity-como-agregado-raiz.md), não corrigida retroativamente no documento (por princípio: ADR não é revogada por edição do texto anterior).
