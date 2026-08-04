# ADR-0056: `VALIDATION_REPORT.md` como artefato obrigatório de toda Release

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O [ADR-0048](0048-processo-quatro-fases.md) define quatro fases por Release — Proposal → Architecture Review → Implementation → Validation — e o [ADR-0054](0054-tres-niveis-de-validacao.md) define os três níveis que compõem a fase de Validation (Testes Automatizados, Architecture Validation, Scenario Validation). Nenhum dos dois, porém, define **o artefato** que a fase de Validation produz. Na Release 2, a evidência de validação ficou dispersa: números de teste citados em texto de conversa, comandos `curl` executados mas não registrados, o estado real do Docker mencionado apenas no Decision Log. Nada disso é um documento único e verificável — é preciso reconstruir a partir do histórico da conversa, o que não escala e não serve como prova para quem não participou dela.

O [Decision Log](0047-decision-log-por-release.md) (ADR-0047) já cobre um propósito diferente: registrar decisões locais e por quê — não é, e não deve virar, um relatório de execução.

## Decisão

Toda Release passa a produzir um `VALIDATION_REPORT.md` em `docs/releases/000N-<slug>-validation-report.md`, escrito ao final da fase de Implementation como evidência da fase de Validation — prova de execução, não documentação descritiva. Estrutura fixa, nesta ordem:

1. **Release** — número e nome.
2. **Ambiente** — SO, versão de PHP real usada, data da execução.
3. **PHP** — versão-alvo (ADR-0009) vs. versão efetivamente usada, com o gap sinalizado quando existir.
4. **Docker** — se o build/up foi executado de fato, ou por que não (nunca omitido).
5. **HTTP** — cada endpoint testado via requisição real, com o comando e o código de status obtido.
6. **Testes** — contagem de testes e assertions por pacote/serviço, com o comando usado para reproduzir.
7. **Coverage** — cobertura de código quando medida; "não medida nesta Release" quando não.
8. **Scenario Validation** — lista dos cenários da proposta (ver ADR-0054) e o resultado de cada um.
9. **Pendências** — tudo que não foi validado, sem exceção — inclusive quando a causa é "aceito conscientemente pelo Product Owner" (nesse caso, com a referência à decisão).

O [template](../releases/VALIDATION_REPORT.template.md) fica em `docs/releases/`.

## Consequências

- A fase de Validation do ADR-0048 ganha um artefato concreto e obrigatório — antes, "Validation" era uma fase sem entregável nomeado.
- Toda alegação de "testado" ou "validado" em qualquer Release, passada ou futura, é verificável por um documento, não por busca no histórico da conversa.
- Custo: mais um documento por Release, escrito depois do código — mas reaproveita dados já coletados durante a Implementation (números de teste, saídas de `curl`), não exige trabalho novo de investigação.
- Aplica-se retroativamente à Release 2, cujo `VALIDATION_REPORT.md` é escrito nesta mesma rodada usando os dados já obtidos durante a implementação e validação originais.
