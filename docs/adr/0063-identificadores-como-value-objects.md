# ADR-0063: Identificadores de domínio são Value Objects, nunca string primitiva

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) define dez entidades, a maioria delas referenciando outras por identificador (`tenant_id`, `workspace_id`, `role_id`, etc.). Representar cada um desses identificadores como `string` primitiva é o padrão mais comum e mais barato de escrever — e também uma fonte clássica de bugs silenciosos: nada impede, na assinatura de uma função, passar um `$workspaceId` no lugar onde um `$userId` era esperado, porque para o compilador os dois são apenas `string`. O mesmo problema se repetiria em todo Engine futuro que criar seus próprios identificadores (`MissionId`, `CapabilityId`, etc.).

## Decisão

Todo identificador de domínio é um **Value Object** dedicado, nunca uma `string` primitiva — a partir da Release 3, começando por `TenantId`, `CompanyId`, `WorkspaceId`, `UserId`, `TeamId`, `RoleId`, `PermissionId`, `SessionId` e `IdentityId` (o identificador do objeto raiz descrito em [IDENTITY_LIFECYCLE.md](../../IDENTITY_LIFECYCLE.md)). Cada um vive em `Domain/` do Engine correspondente ([ADR-0061](0061-engine-quatro-camadas-ddd.md)), envolve um valor primitivo (string UUID, via `Sigma\Core\Id`, ver `packages/core/src/Id.php`), é imutável, e implementa igualdade por valor — dois `WorkspaceId` com o mesmo valor são iguais, mesmo sendo instâncias diferentes.

Este princípio não fica restrito à Release 3 — todo Engine futuro que introduzir um identificador de domínio próprio segue o mesmo padrão.

## Consequências

- Erro de "passar o identificador errado no lugar errado" vira erro de tipo, pego antes mesmo de rodar um teste, em vez de um bug silencioso descoberto em produção.
- Assinaturas de método ficam autoexplicativas: `assignRole(UserId $user, RoleId $role, WorkspaceId $scope)` é inequívoco de um jeito que `assignRole(string $a, string $b, string $c)` nunca é.
- Custo: mais uma classe pequena por identificador — mitigado por serem Value Objects triviais (um construtor, um `equals()`, um `toString()`), não lógica de negócio de verdade.
- Fronteiras de serialização (Envelope JSON, linhas de banco) continuam usando `string` — a conversão para o Value Object acontece na borda de entrada de `Domain`/`Application`, nunca exigindo que o formato de transporte em si mude.
