# ADR-0054: Três níveis de validação obrigatórios por Release

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até aqui, "Testes" na proposta de uma Release cobria apenas testes automatizados. Isso verifica comportamento unitário/integração, mas não verifica se a implementação de fato respeita as decisões arquiteturais já registradas (ADRs, Contracts, Protocol, Manifest) nem se o sistema se comporta corretamente em cenários reais de operação — cobertura de teste alta não garante nenhuma das duas coisas.

## Decisão

Nenhuma Release é considerada pronta sem três níveis de validação:

1. **Testes Automatizados** — unitários e de integração, conforme o escopo da Release.
2. **Architecture Validation** — a implementação respeita ADRs, Contracts, SIGMA Protocol e System Manifest relevantes à Release.
3. **Scenario Validation** — cenários reais de operação são executados e passam. Para a Release 2, por exemplo: sistema sobe sem módulos opcionais; sistema sobe com módulo opcional ausente; um módulo entra em `degraded`; o System Manifest tem erro; um módulo incompatível é rejeitado; `/health/ready` responde corretamente em cada caso.

## Consequências

- Cobertura de teste deixa de ser o único proxy de qualidade — uma Release pode ter testes unitários passando e ainda assim violar uma ADR ou falhar num cenário real; os três níveis existem para pegar isso.
- Toda proposta de Release, a partir de agora, lista cenários de Scenario Validation explícitos na seção de Testes — não apenas "testar o código", mas "testar o sistema se comportando".
- Aumenta o esforço de cada Release (três níveis, não um) — aceito conscientemente como o custo de "um produto vive ou morre pela qualidade dos testes", citando a razão dada nesta revisão.
