# ADR-0008: Arquitetura orientada a eventos com Redis como backbone

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Contextos delimitados do domínio (Mission, Skill, Agent, Knowledge, Memory, Automation...) precisam reagir a mudanças de estado uns dos outros — por exemplo, Knowledge/Memory aprendem a partir de Missões concluídas, e Automation reage a eventos para disparar novas Missões — sem ficarem acoplados por chamada direta de código.

## Decisão

Comunicação entre contextos delimitados acontece exclusivamente por eventos de domínio publicados num Event Bus, com Redis como backbone (filas, pub/sub, broadcasting via WebSocket). Um contexto nunca chama um método de outro contexto diretamente.

## Consequências

- Novos contextos podem reagir a eventos existentes (ex: um futuro contexto de Billing reagindo a `MissionCompleted`) sem que o Mission Engine precise saber que esse consumidor existe.
- Falhas de um consumidor de evento não derrubam o publisher — degradação é isolada por contexto.
- Exige disciplina de nomenclatura e versionamento de eventos (ver `docs/conventions/naming-conventions.md`) e observabilidade sobre o Event Bus desde cedo, para não virar uma rede de eventos "silenciosos" e difíceis de depurar.
- Consistência entre contextos é eventual, não transacional — aceito conscientemente; nenhuma regra de negócio pode depender de dois contextos estarem sincronizados no mesmo instante.
