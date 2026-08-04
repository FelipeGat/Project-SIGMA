# ADR-0067: `Team` é tipado — System Team vs. Business Team

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[DOMAIN.md](../../DOMAIN.md) já definia `Team` como "um agrupamento de Users, com escopo de permissão e visão sobre Missions" — mas sem nenhuma distinção interna. O Product Owner pediu dois tipos: "System Team. CTO, Developer, Support. Business. Comercial, Financeiro, Obras. Isso permitirá automações futuras."

## Decisão

`Team` (`packages/identity-engine/src/Domain/Team.php`) carrega um `TeamType` (`packages/identity-engine/src/Domain/TeamType.php`), enum com dois casos: `System` e `Business`. A distinção é armazenada desde a primeira versão do agregado — nenhuma automação que discrimine por tipo existe ainda nesta Release (isso é trabalho de Releases futuras, provavelmente Automation Engine), mas o campo existe desde já para não exigir uma migration retroativa depois.

## Consequências

- Um Team sempre declara seu tipo na construção — não há "Team sem tipo" no modelo.
- Nenhuma regra de negócio desta Release (3A) discrimina por `TeamType` ainda — é dado estrutural, não comportamento, por enquanto.
- Abre caminho para regras futuras diferenciadas (ex: um System Team ganhando Permissions técnicas por padrão, um Business Team nunca aparecendo em fluxos de automação de infraestrutura) sem precisar alterar o schema quando essas regras forem escritas.
