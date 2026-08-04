# ADR-0034: Eventos em três camadas — Technical, Semantic, Business

- **Status**: Aceito — estende [ADR-0018](0018-tudo-e-evento.md)
- **Data**: 2026-08-04

## Contexto

O catálogo de eventos definido em [ADR-0018](0018-tudo-e-evento.md) cobre a orquestração interna entre Engines (`IntentDetected`, `SkillRequested`...) — necessário para o sistema funcionar, mas pouco significativo para quem quer entender o negócio (ex: "quantos orçamentos foram aprovados este mês"). Misturar os dois níveis no mesmo catálogo, sem distinção, tornaria qualquer consumo por Analytics ruidoso — cheio de eventos técnicos irrelevantes para quem só quer um marco de negócio.

## Decisão

Eventos passam a ter três camadas: **Technical** (orquestração entre Engines — o catálogo canônico já existente), **Semantic** (resultado de uma Capability específica, declarado em `events_emitted` no manifest de Plugin) e **Business** (marcos curados, com nome de negócio, derivados de um ou mais eventos Semantic, destinados a Analytics e Automation). Detalhamento em [EVENT_MODEL.md](../../EVENT_MODEL.md).

## Consequências

- Analytics (Release 13) consome apenas a camada Business, um conjunto pequeno e deliberadamente curado — evita que dashboards fiquem poluídos por eventos técnicos.
- A curadoria de quais eventos Semantic viram Business é uma decisão explícita tomada no épico do Automation/Analytics Engine, não automática — um evento técnico nunca "vaza" para a camada Business sem essa curadoria.
- Cada evento do catálogo (existente e futuro) precisa declarar a que camada pertence — checklist adicional ao propor um novo evento a partir de agora.
