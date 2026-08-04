# ADR-0035: Digital Twin — SIGMA nunca lê um sistema externo diretamente

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Sem uma camada de representação própria, cada leitura de contexto de negócio (o Planner resolvendo um Workspace, um Agent montando contexto para uma Subtask) exigiria uma chamada de API ao sistema externo correspondente — lento, frágil a instabilidade externa, e sem histórico auditável de "o que o SIGMA sabia, e quando".

## Decisão

Client, Project, Company e User possuem um **Digital Twin**: uma representação viva e sincronizada, mantida pelo SIGMA, atualizada a partir de Semantic Events (ver [ADR-0034](0034-eventos-tres-camadas.md)) e de leitura inicial via Capability. Leituras de contexto consultam o Twin; escritas continuam sempre passando por Skill Engine → Plugin → API externa, como já definido em [ADR-0007](0007-comunicacao-somente-via-api.md) — o Twin nunca é a fonte da verdade, é atualizado como consequência de uma escrita real. Especificação completa em [DIGITAL_TWIN.md](../../DIGITAL_TWIN.md).

## Consequências

- Contexto de negócio fica disponível rapidamente para o Planner e os Agents, sem uma chamada de API externa a cada leitura.
- O histórico de eventos que atualizaram um Twin torna seu estado em qualquer momento passado reconstruível — uma forma de auditoria que uma leitura direta e sem estado nunca ofereceria.
- Introduz o risco de um Twin desatualizado (a fonte externa mudou sem um evento correspondente do SIGMA) — mitigado por `warning` explícito no Envelope quando o Twin está fora da janela de refresh esperada, nunca falhando silenciosamente como se estivesse correto.
- Custódia atribuída ao Memory Engine (Release 4) por afinidade — é o Engine já responsável por "o que o SIGMA sabe sobre o mundo" — mas Digital Twin é conceitualmente distinto de Knowledge e de Memory (factual e volátil, não curado nem experiencial).
