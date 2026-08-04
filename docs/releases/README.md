# Propostas formais de Release

Cada Release de código, antes de começar, é apresentada neste formato — Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entregáveis / Testes / Critérios de Aceite — para aprovação explícita do Product Owner. Ver [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md). [ROADMAP.md](../../ROADMAP.md) é a visão macro; esta pasta é o arquivo de cada proposta detalhada, no momento em que ela foi feita.

Toda Release que produz código também produz dois artefatos ao final da implementação:

- **Decision Log** (`000N-<nome>-decision-log.md`) — por que escolhas locais foram feitas dentro do escopo já aprovado, alternativas descartadas, impacto esperado. Ver [ADR-0047](../adr/0047-decision-log-por-release.md).
- **Validation Report** (`000N-<nome>-validation-report.md`) — prova de execução da fase de Validation (ADR-0048): ambiente, versões, testes, HTTP, Scenario Validation, pendências, sempre no formato fixo do [template](VALIDATION_REPORT.template.md). Ver [ADR-0056](../adr/0056-validation-report-obrigatorio.md).

O Decision Log explica o porquê das escolhas; o Validation Report prova o que foi de fato executado. Um não substitui o outro.

| Release | Proposta | Decision Log | Validation Report | Status |
|---|---|---|---|---|
| 2 — SIGMA Bootstrap | [0002-sigma-bootstrap.md](0002-sigma-bootstrap.md) (revisão 3) | [0002-sigma-bootstrap-decision-log.md](0002-sigma-bootstrap-decision-log.md) | [0002-sigma-bootstrap-validation-report.md](0002-sigma-bootstrap-validation-report.md) | ✅ Implementada — 48 testes passando, Validation concluída (docker-compose não verificado, ver Validation Report) |
| 3A — Identity Domain | [0003a-identity-domain.md](0003a-identity-domain.md) (revisão 3) | [0003a-identity-domain-decision-log.md](0003a-identity-domain-decision-log.md) | [0003a-identity-domain-validation-report.md](0003a-identity-domain-validation-report.md) | ✅ Implementada — 50 testes passando, domínio puro sem infraestrutura |
| 3B — Identity Infrastructure | [0003b-identity-infrastructure.md](0003b-identity-infrastructure.md) (revisão 1) | [0003b-identity-infrastructure-decision-log.md](0003b-identity-infrastructure-decision-log.md) | [0003b-identity-infrastructure-validation-report.md](0003b-identity-infrastructure-validation-report.md) | ✅ Implementada — 135 testes no monorepo, `docker compose up --build` validado de fato |
| 3.5 — Architecture Consolidation | [0003.5-architecture-consolidation.md](0003.5-architecture-consolidation.md) | [0003.5-architecture-consolidation-decision-log.md](0003.5-architecture-consolidation-decision-log.md) | [0003.5-architecture-consolidation-validation-report.md](0003.5-architecture-consolidation-validation-report.md) | ✅ Implementada — sem mudar comportamento; validação cruzada encontrou e corrigiu 2 divergências em Contracts |
