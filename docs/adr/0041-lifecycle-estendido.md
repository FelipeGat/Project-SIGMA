# ADR-0041: Lifecycle estendido — discover, register, e o estado degraded

- **Status**: Aceito — estende o ciclo de vida descrito em [ADR-0038](0038-sigma-bootstrap-nao-kernel-completo.md)
- **Data**: 2026-08-04

## Contexto

O ciclo de vida original (`boot → start → ready → shutdown`) não modelava dois momentos reais: como os Modules são encontrados antes de qualquer coisa acontecer, e o que fazer quando um Module específico para de funcionar depois que o sistema já está no ar — sem esse segundo caso, a única opção seria tratar qualquer falha parcial (ex: a integração com o Telegram cair) como se o sistema inteiro tivesse falhado.

## Decisão

O Lifecycle passa a ser: **`discover → register → boot → start → ready → degraded → shutdown`**. `discover` é a leitura do [System Manifest](../../SYSTEM_MANIFEST.md) e localização dos Modules correspondentes, antes de `register` (que só declara bindings, sem executar nada). `degraded` não é uma etapa linear — é um estado que qualquer Module individual pode assumir depois de `ready`, sem derrubar o restante do sistema. Detalhamento em [BOOTSTRAP.md](../../BOOTSTRAP.md).

## Consequências

- Uma falha isolada (um Plugin externo fora do ar) deixa de ser um incidente de "sistema inteiro fora" e passa a ser um incidente de "um Module degradado" — visível granularmente em `/health/ready` (ver [ADR-0042](0042-health-estilo-kubernetes.md)).
- `discover` como etapa própria (antes de `register`) torna explícito que o Bootstrap não adivinha o que carregar — ele lê o Manifest primeiro, sempre.
- Exige que cada Module declare, desde sua primeira implementação, como detectar e reportar seu próprio estado `degraded` — não é algo adicionado depois; é parte do contrato `Module` desde a Release 2.
- Automations e Agents que dependem de um Module `degraded` precisam de uma estratégia de fallback ou fila — não coberto em detalhe nesta ADR, fica para o épico do Automation Engine.
