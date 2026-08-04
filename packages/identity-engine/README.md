# packages/identity-engine

Implementação do Identity Engine: responde "quem sou, quem é o usuário, qual empresa, qual workspace, qual tenant, quais permissões, qual nível de autonomia, qual contexto" — deliberadamente separado do Memory Engine, por ser identidade e não memória. Contém o schema fundacional de multiempresa — Tenant, Company, Workspace, User, Team, Role (ver [MULTITENANCY.md](../../MULTITENANCY.md)) — desde a primeira migration, e a resolução do nível de Autonomia Progressiva por User/Role (ver [SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva)). Ver [ADR-0039](../../docs/adr/0039-identity-engine.md).

Todo Engine a partir da Release 4 consome o contexto de identidade já resolvido aqui, disponibilizado pelo Kernel — nenhum outro Engine resolve Tenant/Workspace/permissão por conta própria.

Vazio na Fase Foundation. Camada/Release 3 do [ROADMAP.md](../../ROADMAP.md), logo após o SIGMA Bootstrap — antes do Memory Engine.
