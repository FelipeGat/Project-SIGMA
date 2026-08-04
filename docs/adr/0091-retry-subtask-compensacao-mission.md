# ADR-0091: Retry é histórico de Subtask; Compensação é estado da Mission

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O Product Owner pediu que Mission modelasse "retries" e "compensações" (ver [ROADMAP.md](../../ROADMAP.md#release-5--mission-engine)) — nenhum documento existente definia a mecânica de nenhum dos dois; `ARCHITECTURE.md §6` tinha só um loop simples (`Validando → EmExecucao: validação falhou, retry`), sem distinguir retry transiente de falha definitiva, e nenhuma noção de desfazer efeito já produzido.

## Decisão

Os dois conceitos vivem em níveis diferentes do modelo, deliberadamente:

- **Retry é histórico da `Subtask`, nunca um `MissionStatus` próprio.** Uma falha transiente gera um `RetryAttempt` (ver [MISSION_MODEL.md — RetryAttempt](../../MISSION_MODEL.md#retryattempt)); a Mission continua `InProgress` durante todas as tentativas. Só quando a política de retry se esgota é que a falha se torna definitiva e o `MissionStatus` pode mudar.
- **Compensação é estado da `Mission`, não só da `Subtask`.** Quando uma Subtask falha definitivamente **depois de já ter produzido efeito** em algum sistema externo, o que precisa ser resolvido não é mais "esta Subtask" isoladamente — é o que a Mission inteira já fez até aqui. Por isso `Compensating` é um `MissionStatus` de primeira classe (ver [MISSION_LIFECYCLE.md — Fluxo 3](../../MISSION_LIFECYCLE.md#fluxo-3--compensação)), e uma Mission que passa por ele **sempre** termina em `Failed` — mesmo que a compensação em si tenha sucesso, o objetivo original não foi alcançado.

## Consequências

- A distinção "a Subtask já produziu efeito ou não" (o gatilho para ir a `Compensating` em vez de simplesmente `Failed`) não é resolvida por este ADR — é decisão de Implementation, informada por metadados já existentes na Capability (`riskLevel` do Envelope). Sinalizado explicitamente em [MISSION_MODEL.md — O que este modelo não decide](../../MISSION_MODEL.md#o-que-este-modelo-não-decide).
- Nenhuma taxonomia fechada de tipo de compensação existe ainda — `Compensation.action` é texto livre nesta Release. Generalizar em tipos (ex: por Capability) é decisão futura, quando houver Capabilities reais o suficiente para observar um padrão.
- Número exato de tentativas de retry e política de backoff ficam como parâmetro de configuração, não constante de código — mesmo padrão já usado para os pisos de `confidence` do Memory Engine ([MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md)).
- Um cancelamento manual (`Cancelled`) nunca aciona compensação automaticamente — se um cancelamento precisa desfazer efeito já produzido, isso é decisão de quem cancela, não uma reação automática do Mission Engine (evita o Engine tomar uma ação de compensação sem ter sido ele mesmo quem decidiu que havia falha).
