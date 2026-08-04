# services/auth

Autenticação e autorização do SIGMA: User, Team, Company, e a hierarquia completa Tenant → Company → Workspace → User → Role descrita em [MULTITENANCY.md](../../MULTITENANCY.md). Modelado desde o schema para multiempresa — nunca retrofitado (ver [ADR-0021](../../docs/adr/0021-multitenancy-desde-o-schema.md)).

Vazio na Fase Foundation. Fundações de Tenant/Company/Workspace/Role entram já na camada L1 — Kernel do [ROADMAP.md](../../ROADMAP.md), mesmo que a superfície completa de auth amadureça depois.
