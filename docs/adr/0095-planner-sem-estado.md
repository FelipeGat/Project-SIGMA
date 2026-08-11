# ADR-0095: O Planner Engine é sem estado

- **Status**: Aceito
- **Data**: 2026-08-11

## Contexto

Todo Engine construído até aqui persiste algo: Identity guarda a hierarquia e as sessões, Memory guarda o que se aprendeu, Mission guarda o aggregate e seu histórico. Ao modelar o Planner (Release 6A), a primeira pergunta estrutural foi se ele segue esse padrão — se um `Plan` produzido é guardado por quem o produziu.

A pergunta decide o tamanho da Release inteira: com estado, há `Infrastructure/` com repositório, migrations, tabelas e testes contra MariaDB; sem estado, a Release é `Domain/` + `Application/` e nada mais.

Há um argumento real a favor de persistir: histórico de planejamento é útil para auditoria ("por que o sistema decidiu isso, naquele dia?") e é pré-requisito de replanejamento.

## Decisão

O Planner Engine **não persiste nada**. Recebe uma Intent, produz um `Plan` (ou um `PlanningFailure`), publica o evento correspondente e esquece. `packages/planner-engine` não terá repositório, migrations nem tabelas.

Quem guarda o Plan é o Mission Engine, que já o persiste como parte do aggregate `Mission` desde a Release 5C.

## Consequências

- **Uma única fonte de verdade sobre "qual é o plano desta Mission".** Um Planner com memória própria criaria uma segunda, e a primeira divergência entre as duas seria um bug silencioso e caro de diagnosticar.
- **A Release 6 fica substancialmente menor** — sem `Infrastructure/` de persistência, sem migrations, sem testes contra banco.
- **Custo aceito: o Planner não sabe o que já planejou.** Sem histórico de planejamento e sem replanejamento. A auditoria de "por que o sistema decidiu isso" fica disponível pelo evento `mission.planned` (Audit Engine, Release 11), não por consulta ao Planner.
- **Custo aceito, mais incômodo: o Planner nunca sabe se a Mission chegou a existir.** Se ninguém consumir `mission.planned`, o Plan se perde e ele não tem como detectar. Agravado pela ausência de replay no Redis pub/sub, confirmada com evidência real na [Release 4.5](../releases/0004.5-platform-validation-validation-report.md). Registrado como pendência real, não como característica desejável.
- **Reversível sem quebrar contrato**: acrescentar persistência depois não muda o payload de nenhum evento nem a forma do `Plan`. É uma Release aditiva, não uma correção de modelagem.
