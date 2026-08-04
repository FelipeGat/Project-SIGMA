# ADR-0019: Observabilidade (Logs, Metrics, Tracing, Audit) desde o dia zero

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Observabilidade costuma ser tratada como melhoria posterior — algo que se adiciona quando o sistema já está grande o suficiente para doer sem ela. Para uma plataforma cujo valor central é orquestrar ações em nome de pessoas e sistemas reais (ver [MANIFESTO.md](../../MANIFESTO.md)), operar sem visibilidade desde o início significa não conseguir responder "o que o SIGMA fez, e por quê" no primeiro incidente — exatamente quando essa resposta mais importa.

## Decisão

Telemetry (Logs, Metrics, Tracing) e Audit são requisitos não funcionais de primeira classe desde o primeiro Engine implementado (Release 2 — Kernel), não uma tarefa adicionada depois. Detalhamento em [TELEMETRY.md](../../TELEMETRY.md), incluindo a distinção entre Telemetry (infraestrutural) e Audit Engine (domínio de negócio).

## Consequências

- Toda Release de Engine, a partir da Release 2, inclui como Critério de Aceite a instrumentação básica (logs estruturados, correlação por `trace_id`) — não é uma tarefa "se sobrar tempo".
- O Kernel carrega o bootstrap de Telemetry ([KERNEL.md](../../KERNEL.md)) antes de qualquer Engine de negócio inicializar, garantindo que nenhum Engine rode sem observabilidade mínima.
- Custo real: cada épico carrega um pouco mais de trabalho de instrumentação desde o início, mesmo quando o volume de uso ainda não justificaria isso por si só — aceito conscientemente porque o custo de instrumentar depois, com o sistema já em produção e sem esse hábito estabelecido, é maior.
- Dashboards e alertas (Release 14 — Analytics) só são possíveis porque a base de dados de Telemetry/Audit já existe desde o início — sem essa decisão, a Release 14 exigiria retrofitar tudo isso primeiro.
