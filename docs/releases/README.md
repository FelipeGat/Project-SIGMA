# Propostas formais de Release

Cada Release de código, antes de começar, é apresentada neste formato — Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entregáveis / Testes / Critérios de Aceite — para aprovação explícita do Product Owner. Ver [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md). [ROADMAP.md](../../ROADMAP.md) é a visão macro; esta pasta é o arquivo de cada proposta detalhada, no momento em que ela foi feita.

Toda Release que produz código também produz um **Decision Log** (`000N-<nome>-decision-log.md`), escrito ao final da implementação — por que escolhas locais foram feitas dentro do escopo já aprovado, alternativas descartadas, impacto esperado. Ver [ADR-0047](../adr/0047-decision-log-por-release.md).

| Release | Proposta | Decision Log | Status |
|---|---|---|---|
| 2 — SIGMA Bootstrap | [0002-sigma-bootstrap.md](0002-sigma-bootstrap.md) (revisão 3) | [0002-sigma-bootstrap-decision-log.md](0002-sigma-bootstrap-decision-log.md) | ✅ Implementada — 48 testes passando, Validation concluída (docker-compose não verificado, ver Decision Log) |
