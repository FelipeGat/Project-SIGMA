# ADR-0039: Identity Engine — extraído do Memory Engine, Release própria

- **Status**: Aceito — reabre e substitui a proposta de [ADR-0038](0038-sigma-bootstrap-nao-kernel-completo.md) de mover o schema de multiempresa para o Memory Engine
- **Data**: 2026-08-04

## Contexto

[ADR-0038](0038-sigma-bootstrap-nao-kernel-completo.md) propôs mover o schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role), removido do escopo da Release 2, para o Memory Engine — por ser o próximo Engine na fila. Em revisão, o Product Owner apontou que isso mistura duas responsabilidades fundamentalmente diferentes: Memory responde "o que sei, o que aprendi, o que aconteceu" (factual/experiencial); identidade responde "quem sou, quem é o usuário, qual empresa, qual workspace, qual tenant, quais permissões, qual autonomia, qual contexto" — uma pergunta de natureza completamente distinta.

## Decisão

Cria-se o **Identity Engine** como Release própria (Release 3, entre Bootstrap e Memory). Ele possui o schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role — ver [MULTITENANCY.md](../../MULTITENANCY.md) e [ADR-0021](0021-multitenancy-desde-o-schema.md)) e a resolução do nível de Autonomia Progressiva por User/Role (ver [ADR-0029](0029-autonomia-progressiva.md)). O roadmap passa a ser: Foundation → Protocol → Bootstrap → **Identity** → Memory → Mission → Planner → Intent → Skill → Agent → Execution → Audit → Interfaces → Automation → Analytics.

## Consequências

- Memory Engine (Release 4) fica focado exclusivamente em Knowledge/Memory/Digital Twin — sem carregar responsabilidade de autenticação/autorização que não é dele.
- Todo Engine a partir da Release 4 consome contexto de identidade (Tenant/Workspace/User/nível de autonomia) já resolvido pelo Identity Engine através do Kernel — nunca resolve isso por conta própria, reforçando a regra de [KERNEL.md](../../KERNEL.md) de que o Kernel apenas disponibiliza contexto, não o resolve.
- Renumera todas as Releases de 3 a 13 para 4 a 14 — mudança mecânica, sem alterar a natureza de nenhuma delas.
- `services/auth` (já especificado desde a Sprint 0.2) é o serviço deployável correspondente ao Identity Engine — sua descrição é atualizada para refletir esta Release.
