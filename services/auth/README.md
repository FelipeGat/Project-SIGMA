# services/auth

Autenticação e autorização do SIGMA — a superfície deployável do **Identity Engine** (ver [packages/identity-engine](../../packages/identity-engine/) e [ADR-0039](../../docs/adr/0039-identity-engine.md)): User, Team, Company, e a hierarquia completa Tenant → Company → Workspace → User → Role descrita em [MULTITENANCY.md](../../MULTITENANCY.md). Modelado desde o schema para multiempresa — nunca retrofitado (ver [ADR-0021](../../docs/adr/0021-multitenancy-desde-o-schema.md)).

Vazio na Fase Foundation. Fundações de Tenant/Company/Workspace/Role na Release 3 — Identity Engine do [ROADMAP.md](../../ROADMAP.md), logo após o SIGMA Bootstrap (Release 2, reduzida a infraestrutura pura — ver [ADR-0038](../../docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md)).
