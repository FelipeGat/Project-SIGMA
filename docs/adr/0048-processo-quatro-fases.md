# ADR-0048: Toda Release segue quatro fases — Proposal, Architecture Review, Implementation, Validation

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até a Release 2, o processo era: proposta formal ([ADR-0010](0010-processo-por-epicos-com-aprovacao.md)) → aprovação → código. Isso já exige aprovação antes do código, mas não separa explicitamente "a proposta foi revisada tecnicamente" de "a proposta foi aprovada" — na prática, a Release 2 passou por uma revisão de arquitetura real (Identity Engine extraído, Bootstrap desacoplado de Engines) só na segunda rodada, informalmente.

## Decisão

Toda Release segue quatro fases explícitas: **Proposal** (o formato de [ADR-0010](0010-processo-por-epicos-com-aprovacao.md)) → **Architecture Review** (revisão técnica da proposta, podendo gerar mudanças antes de qualquer código) → **Implementation** (execução exata do que foi aprovado na Architecture Review — nada além) → **Validation** (os três níveis de [ADR-0054](0054-tres-niveis-de-validacao.md)). A partir de agora, uma instrução de "desenvolva a Release N" é substituída por "implemente exatamente o que foi aprovado na Proposal" — a instrução de implementação não reabre escopo.

## Consequências

- Reduz o risco de escopo "andar" durante a implementação — o que foi aprovado na Architecture Review é o que é construído, não uma interpretação livre a partir de um objetivo geral.
- Mudanças de escopo descobertas durante a Implementation voltam para uma nova rodada de Proposal/Architecture Review — não são absorvidas silenciosamente no meio do código.
- Formaliza, retroativamente, o que já aconteceu na prática com a Release 2 (duas rodadas de revisão antes do código) — não muda nada da Release 2 em si, só nomeia o padrão para as seguintes.
