# ADR-0043: Bootstrap inicializa Telemetry completa — não apenas um Logger

- **Status**: Aceito — aplica [ADR-0019](0019-observabilidade-desde-o-dia-zero.md) especificamente ao componente de Bootstrap
- **Data**: 2026-08-04

## Contexto

[ADR-0019](0019-observabilidade-desde-o-dia-zero.md) já estabelece observabilidade desde o dia zero como princípio geral. Ao desenhar o Bootstrap em detalhe, havia o risco de simplificar isso, na prática, para "inicializar um logger" — suficiente para mensagens de texto, mas insuficiente para os outros três pilares (Metrics, Tracing, Audit) já definidos em [TELEMETRY.md](../../TELEMETRY.md).

## Decisão

O componente que o Bootstrap inicializa logo após o Configuration Provider não é um "Logger" — é **Telemetry**, cobrindo os quatro pilares (Logs, Metrics, Tracing, Audit) desde a primeira etapa do [Lifecycle](../../BOOTSTRAP.md#como-o-sigma-inicia). Ver [BOOTSTRAP.md § Telemetry, não apenas Logger](../../BOOTSTRAP.md#telemetry-não-apenas-logger).

## Consequências

- Nenhum Module, desde o primeiro implementado na Release 2, precisa "adicionar observabilidade depois" — Metrics e Tracing já existem como capacidade desde o boot, mesmo que o volume de uso ainda não justifique olhar para eles.
- O `correlationId`/`requestId` do [Envelope](../../SIGMA_PROTOCOL.md#1-o-envelope) já tem, desde a Release 2, uma infraestrutura de Tracing capaz de correlacionar chamadas — não é um campo decorativo até que um Engine de domínio exista.
- Custo real: a Release 2 carrega mais trabalho de infraestrutura do que "só logar em arquivo" exigiria — aceito conscientemente pela mesma razão de [ADR-0019](0019-observabilidade-desde-o-dia-zero.md).
