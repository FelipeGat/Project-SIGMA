# ADR-0051: RFC — ideias antes da decisão

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

ADR registra uma decisão já tomada. Faltava um espaço para discutir uma ideia **antes** de ela estar madura o suficiente para virar decisão — sem esse espaço, ideias em aberto tendem a ser decididas rápido demais (viram ADR prematura) ou não ficam registradas em lugar nenhum (vivem só em conversa).

## Decisão

Cria-se `docs/rfc/` — um RFC por ideia em discussão, seguindo [docs/rfc/template.md](../../docs/rfc/template.md). Fluxo: RFC → Discussão → Aprovação → ADR → Código. Um RFC aceito gera uma ADR (a decisão); um RFC não é, por si só, autorização para código.

## Consequências

- Ideias com alternativas genuinamente abertas ganham um lugar para registrar o raciocínio de descarte de cada alternativa — não só a escolhida.
- Reduz decisões precipitadas: a existência do RFC como etapa intermediária cria espaço deliberado para questionar antes de decidir.
- Nem toda decisão precisa de RFC — decisões já claras dentro do escopo de uma Release aprovada continuam indo direto para Decision Log ([ADR-0047](0047-decision-log-por-release.md)); RFC é para o que ainda não está claro.
