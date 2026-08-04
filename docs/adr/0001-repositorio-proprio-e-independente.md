# ADR-0001: SIGMA vive em repositório próprio, independente dos sistemas que orquestra

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O SIGMA precisa orquestrar Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam e futuros sistemas do ecossistema Alfa. Havia duas opções: nascer como módulo dentro de um sistema já existente (ex: Gestor.Alfa) ou nascer como sistema independente que se comunica com os demais via API.

## Decisão

SIGMA é um repositório e uma aplicação própria (`project-sigma`, renomeado de `Sigma-IO` — ver [memory/DECISIONS.md](../../memory/DECISIONS.md)), desacoplada de qualquer sistema de negócio existente. Ele não é dono de dados de negócio de nenhum outro sistema; é dono apenas do seu próprio domínio de orquestração (Mission, Skill, Agent, Knowledge, Memory...).

## Consequências

- Nenhum sistema existente precisa ser alterado internamente para o SIGMA existir.
- SIGMA pode ser desenvolvido, versionado e implantado de forma independente.
- Toda integração com um sistema existente exige uma API pública desse sistema (ou a criação dela) — não há atalho de acesso direto a banco. Ver [ADR-0007](0007-comunicacao-somente-via-api.md).
- Se um sistema existente não expõe API para o que o SIGMA precisa, esse é um custo explícito a ser resolvido no sistema de origem, não contornado no SIGMA.
