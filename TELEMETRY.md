# Telemetry

Observabilidade é requisito de primeira classe do SIGMA desde o primeiro Engine implementado — não uma camada adicionada depois que o sistema já está em produção e algo já deu errado sem explicação. Ver [ADR-0019](docs/adr/0019-observabilidade-desde-o-dia-zero.md).

## Os quatro pilares

| Pilar | Responde | Exemplo |
|---|---|---|
| **Logs** | O que aconteceu, em detalhe | "Skill `GestorSkill.criar_orcamento` falhou: timeout após 5s" |
| **Metrics** | Quanto, com que frequência | Taxa de falha de `GestorSkill` nos últimos 7 dias; tempo médio de uma Mission do tipo `novo-orcamento` |
| **Tracing** | O caminho completo de uma execução através dos Engines | Uma única `trace_id` acompanhando `MissionRequested` → ... → `MissionFinished`, atravessando Intent, Planner, Mission, Agent, Skill, Execution |
| **Audit** | Quem fez o quê, para quê, com que resultado — de forma correlacionável a uma Mission de negócio | Ver [Audit Engine](docs/architecture/ARCHITECTURE.md) |

## Telemetry vs. Audit Engine — a distinção que importa

**Audit Engine** é um Engine de domínio: possui as entidades `Event` e `Log` (ver [DOMAIN.md](DOMAIN.md)), e sua razão de existir é rastreabilidade de negócio — responder "o que o SIGMA fez em nome de quem, e por quê" de um jeito que faz sentido para uma pessoa de negócio auditar.

**Telemetry** é a camada de infraestrutura que instrumenta *todos* os Engines e Plugins, incluindo detalhes técnicos sem significado de negócio (latência de uma chamada HTTP, uso de memória, taxa de erro de um provedor de IA). É consumida por quem opera a plataforma, não por quem audita uma Mission específica.

Na prática: todo evento relevante do Audit Engine também gera telemetria técnica, mas nem toda telemetria técnica vira um registro de Audit — um retry de rede bem-sucedido é telemetria; a Mission que ele fez parte de completar é Audit.

## O que é instrumentado desde o Kernel

- Todo Engine emite Logs estruturados (formato único, correlacionável por `trace_id` e `mission_id` quando aplicável) desde sua primeira versão — não como melhoria posterior.
- Toda invocação de Skill/Plugin é medida (latência, sucesso/falha) — ver o campo `events` do [manifest de Plugin](PLUGIN_SYSTEM.md).
- Toda cadeia de eventos de uma Mission (ver [EVENT_MODEL.md](EVENT_MODEL.md)) é rastreável ponta a ponta por uma única trace.

## Onde vive

Bootstrap de Telemetry pertence ao [Kernel](KERNEL.md). Persistência e consulta de Audit pertencem a [packages/audit-engine](packages/audit-engine/). Dashboards consumindo essa base pertencem à camada L12 — Analytics do [ROADMAP.md](ROADMAP.md).
