# ADR-0093: `Mission.workspaceId` é opcional

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[WORKSPACES.md](../../WORKSPACES.md) já dizia que uma Mission "tipicamente" executa dentro de um Workspace — nunca "sempre". [MULTITENANCY.md](../../MULTITENANCY.md) fixa `tenant_id` como obrigatório em toda tabela de domínio, mas deixa `workspace_id` condicional "quando aplicável ao domínio da tabela". `MISSION_MODEL.md` precisava de uma decisão explícita, não apenas herdar a ambiguidade do "tipicamente".

## Decisão

`Mission.workspaceId` é opcional (nullable). A grande maioria das Missions nasce dentro de um Workspace — o contexto de negócio (Client/Project/Budget/Meeting) que dá sentido a quase toda ação real do SIGMA. Mas uma Mission de sistema/manutenção (ex: reindexar `/knowledge`, rodar uma verificação de saúde, uma futura Mission de plataforma como as validações da Release 4.5) não tem, nem deveria simular ter, um Workspace de cliente associado.

## Consequências

- `tenant_id` continua obrigatório sem exceção (ADR-0021) — só `workspace_id` é condicional, mesmo padrão já usado por `ContextMemory`/`MemoryRecord` no Memory Engine (`workspaceId` obrigatório em Operational/Project, ausente em LongTerm) e agora estendido a Mission por um motivo diferente (Mission de sistema, não nível de Memory).
- Todo consumidor de `Mission.workspaceId` (Interfaces, Release 13; Audit Engine, Release 11) precisa tratar `null` como um valor válido e esperado — nunca um erro de dado incompleto.
- `actor.type: "system"` (ver [SIGMA_PROTOCOL.md §1](../../SIGMA_PROTOCOL.md#1-o-envelope)) é o sinal mais provável de uma Mission sem Workspace, mas os dois campos são independentes — uma Mission de `actor.type: "user"` também pode, em tese, não ter Workspace (ex: uma ação administrativa sobre o próprio Tenant).
