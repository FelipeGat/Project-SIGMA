# ADR-0097: O Planner falha visivelmente — sem template genérico de fallback

- **Status**: Aceito
- **Data**: 2026-08-11

## Contexto

Ao modelar como o Planner escolhe um `PlanTemplate` a partir de uma Intent, surgiu a pergunta de o que fazer quando nenhum template atende: cair num template genérico ("tente executar o objetivo como uma única Subtask") ou recusar-se a planejar.

O fallback é tentador porque o sistema "sempre responde alguma coisa" — nenhum pedido fica sem resposta, e a taxa de sucesso aparente sobe.

Também não havia, no catálogo de eventos, nada que representasse "entendi o pedido e não sei como executá-lo". `IntentRejected` existe, mas pertence ao Intent Engine e significa outra coisa.

## Decisão

O Planner **não tem template genérico de fallback**. Quando nenhum `PlanTemplate` atende o `IntentKind`, quando faltam parâmetros obrigatórios, ou quando o template produz zero passos, ele publica **`PlanningFailed`** (`planning.failed`) e não produz Plan algum.

`IntentKind` é um enum fechado, com exatamente um template por valor — não há ambiguidade a resolver nem precedência a definir.

## Consequências

- **O pior modo de falha deste Engine é evitado.** Um Planner que produz um plano genérico para um pedido que não entende faz o SIGMA executar a coisa errada com confiança, até o fim, corretamente. É o risco identificado em [PLANNER_MANIFESTO.md](../../PLANNER_MANIFESTO.md#o-planner-é-onde-o-sigma-decide) — e um sistema que orquestra ação no mundo real (mexer em orçamento, agendar com cliente, ativar ambiente em produção) não tem margem para isso.
- **`PlanningFailed` vira um sinal de produto, não só de erro.** Ele aponta para uma lacuna de Playbook — conhecimento de negócio que ninguém documentou ainda. Ao longo do tempo, a lista de `planning.failed` por `reason: unknown_intent_kind` é literalmente a fila de Playbooks a escrever.
- **A distinção com `IntentRejected` é preservada.** "Não entendi o pedido" (Intent Engine) e "entendi e não sei como fazer" (Planner) apontam para lugares opostos na hora de diagnosticar. Reutilizar um evento para os dois apagaria a distinção justamente quando ela é mais útil.
- **Custo aceito: o SIGMA parece menos capaz no início.** Com sete `IntentKind` e sete Playbooks incompletos, muita coisa vai cair em `PlanningFailed`. É preferível a um sistema que finge saber.
- **As três causas são verificadas em ordem**, e a primeira encerra: uma Intent de `kind` desconhecido com parâmetros faltando reporta `unknown_intent_kind`. Reportar a falha mais externa dá a mensagem acionável.
- **Nenhum Engine consome `planning.failed` ainda** — Audit Engine (Release 11) é o consumidor natural. Mesmo padrão já aceito para vários eventos do Memory Engine na Release 4B.
