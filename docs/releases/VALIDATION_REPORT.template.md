# Release N — <Nome> — Validation Report

Prova de execução da fase de Validation (ver [ADR-0048](../adr/0048-processo-quatro-fases.md) e [ADR-0056](../adr/0056-validation-report-obrigatorio.md)) — não é documentação descritiva, é o registro do que foi de fato executado, com os resultados obtidos. Distinto do [Decision Log](0002-sigma-bootstrap-decision-log.md) (ADR-0047), que registra decisões locais e o porquê; este documento registra execução e evidência.

## Release

Número e nome da Release. Link para a Proposal correspondente.

## Ambiente

- Sistema operacional e versão.
- Data da execução.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada nesta validação: `_._._`.
- Gap sinalizado explicitamente, se houver — nunca omitido.

## Docker

- `docker compose up --build` foi executado de fato? Sim/Não.
- Se não, por quê (ambiente indisponível, decisão deliberada de adiar, etc.) — com referência à decisão, se houver uma.

## HTTP

Para cada endpoint público testado, o comando usado e o código de status obtido — não "deveria retornar", o que retornou de fato.

| Endpoint | Comando | Status obtido |
|---|---|---|
| `/exemplo` | `curl -s -o /dev/null -w "%{http_code}" http://...` | `200` |

## Testes

Contagem de testes e assertions por pacote/serviço, com o comando usado para reproduzir.

| Pacote/Serviço | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/core` | `composer test` | N | N |

**Total**: N testes, todos passando (ou: N passando, M falhando — nunca omitir falhas).

## Coverage

Cobertura de código medida (ferramenta e percentual) ou "não medida nesta Release" — explicitamente, não por omissão.

## Scenario Validation

Cada cenário listado na Proposal da Release, com o resultado real:

- ✅/⚠/❌ `<cenário>` — como foi validado (teste automatizado, HTTP real, manual) e o resultado.

## Pendências

Tudo que não foi validado nesta Release, sem exceção — inclusive quando a causa é uma decisão consciente do Product Owner de adiar (com a referência a essa decisão).
