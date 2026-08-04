# ADR-0060: Uma Release complexa pode se dividir em sub-Releases (Domain-first, Infra depois)

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até a Release 2, cada Release era infraestrutura pura ou uma unidade de escopo pequena o bastante para ser implementada de uma vez. A Release 3 — Identity Engine é a primeira Release de **modelagem de domínio real**: entidades, agregados, regras de negócio, e só depois persistência, API, autenticação. Implementar tudo de uma vez arrisca exatamente o problema que [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) já existe para evitar — desenhar schema de banco antes do modelo de domínio estar maduro, e ter que retrofitar quando o modelo mudar (o mesmo erro que [ADR-0021](0021-multitenancy-desde-o-schema.md) já identificou como um dos mais caros e mais comuns em sistemas corporativos, agora na direção "modelo muda depois que o schema já existe").

## Decisão

Uma Release cujo domínio ainda não foi provado em código pode ser dividida em duas sub-Releases sequenciais, sob o mesmo número: **3A — Domain** (Value Objects, Entities, Aggregates, Contracts, Eventos de domínio, regras de negócio — deliberadamente sem persistência, banco, autenticação ou API) e **3B — Infrastructure** (persistência, repositórios, serviços, API, testes de integração), com **3B só começando depois que 3A estiver implementada e validada**. Cada sub-Release mantém o processo de quatro fases completo ([ADR-0048](0048-processo-quatro-fases.md)) — Proposal, Architecture Review, Implementation, Validation — como se fosse uma Release própria; a única diferença é que ambas compartilham o número 3 e o mesmo objetivo de negócio final.

Esta divisão não é o padrão para toda Release futura — só se aplica quando o domínio sendo modelado é novo e ainda não foi exercitado em código (a condição que torna caro corrigir depois). Releases de infraestrutura ou que estendem um domínio já modelado continuam sendo uma unidade só.

## Consequências

- Reduz drasticamente o risco de retrabalho de schema/persistência por causa de um modelo de domínio que ainda estava mudando.
- Aumenta o tempo total até a Release 3 estar 100% concluída (agora são dois ciclos completos de quatro fases, não um) — aceito conscientemente pelo Product Owner como troca válida pela redução de risco.
- Estabelece um padrão reutilizável: a próxima Release que modelar domínio novo pela primeira vez (ex: Mission Engine, Release 5) pode usar a mesma divisão A/B se o mesmo risco se aplicar — decisão a ser tomada de novo, release a release, não automática.
- `docs/releases/0003-identity-engine.md` (revisão 2, já aprovada) é dividida em `0003a-identity-domain.md` e `0003b-identity-infrastructure.md` — a primeira com escopo completo e aprovada para implementação imediata; a segunda como placeholder, sua Proposal completa só é escrita quando 3A estiver validada.
