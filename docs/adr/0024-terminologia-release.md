# ADR-0024: "Release" substitui "Sprint" como unidade de entrega

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até aqui, o trabalho foi organizado em "Sprints" (0, 0.1, 0.2) — termo que carrega a conotação de iteração de processo ágil interno, adequado enquanto o SIGMA era um exercício de definição de arquitetura. A partir da aprovação da Sprint 0.2 e do primeiro push, o Product Owner declarou o projeto oficialmente iniciado como produto, não mais experimental.

## Decisão

"Sprint" é substituído por **"Release"** como unidade de entrega do SIGMA a partir deste ponto. As Sprints 0, 0.1 e 0.2 (Foundation) são reconhecidas retroativamente como **Release 0 — Foundation**, sem renumeração de seus commits ou documentos históricos. Toda entrega seguinte é numerada como Release 1, 2, 3... — ver ordem completa em [ROADMAP.md](../../ROADMAP.md).

## Consequências

- Reflete a mudança de fase: de "documentação sendo iterada" para "plataforma sendo construída em incrementos versionados".
- Documentos e commits anteriores a esta ADR continuam mencionando "Sprint 0/0.1/0.2" — não são reescritos; é assim que a história realmente aconteceu. `ROADMAP.md` e `memory/*` passam a usar "Release" a partir de agora.
- Abre caminho para que uma Release, no futuro, corresponda a uma versão publicamente identificável da plataforma (ex: tag de versão, changelog) — não apenas a um marco interno de documentação.
