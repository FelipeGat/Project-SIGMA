# ADR-0064: `Identity` é o agregado raiz — não `Context` isolado

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) descrevia originalmente `Context` como um objeto de valor resolvido em runtime, sem um agregado raiz próprio acima dele — User, Workspace, Permissions e Autonomy apareciam como entidades relacionadas, mas nada as compunha sob um único ponto de entrada. O Product Owner apontou, na revisão da Pré-Release 3, que faltava esse objeto raiz: "Identity → User → Workspace → Permissions → Context → Autonomy. Todo o resto deriva da Identity."

## Decisão

`Identity` é o agregado raiz do Identity Engine (`packages/identity-engine/src/Domain/Identity.php`) — compõe `User` e `Tenant`, e expõe o fluxo inteiro de [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md) como métodos que produzem eventos de domínio: `create()` (`IdentityCreated`), `activate()`/`disable()` (`IdentityActivated`/`IdentityDisabled`), `authenticate()` (`SessionStarted`), `selectWorkspace()` (`WorkspaceSelected`), e `resolveContext()`, que produz o `Context` final — mas não publica evento próprio, já que "Identity pronta" é o resultado de todo o fluxo, não um evento adicional catalogado em [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md). `Context` deixa de ser o objeto de mais alto nível e passa a ser o valor imutável que `Identity::resolveContext()` produz — ver [ADR-0066](0066-context-imutavel.md).

## Consequências

- Todo o fluxo de autenticação/resolução tem um único ponto de entrada testável (`Identity`), em vez de operações soltas sobre `User`/`Session`/`Context` sem um dono.
- `IDENTITY_MODEL.md` fica desatualizado neste ponto específico (ainda descreve `Context` como o objeto raiz) — corrigido nesta ADR, não editado retroativamente no documento original, conforme o próprio princípio de ADRs não revogadas por edição.
- Nenhuma mudança de schema é necessária ainda (Release 3A não persiste nada) — o impacto real desta decisão só aparece na Release 3B, quando `IdentityId` precisa existir como coluna própria.
