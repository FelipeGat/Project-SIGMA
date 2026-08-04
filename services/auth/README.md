# services/auth

Autenticação e autorização do SIGMA: User, Team, Company, e a hierarquia completa Tenant → Company → Workspace → User → Role descrita em [MULTITENANCY.md](../../MULTITENANCY.md). Modelado desde o schema para multiempresa — nunca retrofitado (ver [ADR-0021](../../docs/adr/0021-multitenancy-desde-o-schema.md)).

Vazio na Fase Foundation. Fundações de Tenant/Company/Workspace/Role propostas para a Release 3 do [ROADMAP.md](../../ROADMAP.md) — a Release 2 (SIGMA Bootstrap) foi reduzida a infraestrutura pura, ver [ADR-0038](../../docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md) — mesmo que a superfície completa de auth amadureça depois.
