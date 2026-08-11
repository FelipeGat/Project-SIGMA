# Planner Manifesto

O porquê do Planner Engine, antes de qualquer modelagem. Escrito na Release 6A, seguindo o [Processo Oficial de Desenvolvimento de Engines](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md), a partir do levantamento em [Planner Research](docs/releases/0006a-planner-research.md).

## O Planner é onde o SIGMA decide

Todo Engine construído até aqui **guarda** alguma coisa. O Identity guarda quem é quem, o Memory guarda o que se aprendeu, o Mission guarda o que está sendo executado. O Planner não guarda nada — ele **decide**.

É a diferença que torna este Engine o mais crítico do sistema. Um bug no Mission Engine faz uma Mission ficar num estado errado, e isso é visível. Um bug no Planner faz o SIGMA decidir a coisa errada — e executá-la corretamente até o fim.

## O Planner é a resposta a "declarativo, nunca imperativo"

[MANIFESTO.md](MANIFESTO.md) promete que ninguém diz ao SIGMA *"atualize o orçamento"*, e sim *"o orçamento da Sea Master precisa refletir as decisões desta reunião"*. Essa promessa não custou nada até hoje, porque nada precisava traduzir o estado desejado em passos.

**O Planner é quem paga essa conta.** Ele é o único lugar do sistema onde "o quê" vira "como". Se o Planner falhar nisso, a promessa declarativa do SIGMA vira marketing: o usuário volta a ter que especificar o passo a passo, e cada Mission vira um script.

## A IA nunca decide, e isso é o ponto — não uma limitação

[ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md) já decidiu que o Planner decide, nunca a IA. Vale registrar aqui **por que** isso não é conservadorismo:

Uma IA decidindo os passos torna o SIGMA tão previsível quanto o modelo por trás dela naquele mês. O mesmo pedido produziria planos diferentes conforme o provedor, a versão, a temperatura. Auditar "por que o sistema fez isso?" viraria auditar um modelo que não é nosso e que muda sem aviso.

Com o Planner decidindo, trocar de IA muda **como** uma Subtask é executada — nunca **o que** o sistema resolve fazer. É essa fronteira que permite ao [MANIFESTO.md](MANIFESTO.md) afirmar que "toda IA pode ser trocada" sem que isso seja uma aposta.

Quando o Planner usar IA como apoio (sugerir decomposições, por exemplo), o resultado passa pelas regras do próprio Planner antes de virar Plan. Sugestão não é decisão.

## O Planner é sem estado, e isso é uma decisão de desenho

Ele recebe uma Intent, produz um Plan, publica `MissionPlanned` e esquece. Quem guarda o Plan é o Mission Engine, que já o persiste como parte do aggregate desde a Release 5C.

Um Planner com memória própria criaria uma segunda fonte de verdade sobre "qual é o plano desta Mission" — e a primeira divergência entre as duas seria um bug silencioso e caríssimo de diagnosticar. Melhor não ter a segunda.

Consequência aceita conscientemente: **o Planner não sabe o que já planejou.** Não há histórico de planejamento, não há "replanejar a Mission X". Se isso fizer falta, é uma Release própria, com decisão explícita — não um campo acrescentado de passagem.

## Os Playbooks são a matéria-prima, não a implementação

Os sete [Playbooks](playbooks/) existem desde a Fase Foundation justamente para dar ao Planner, em forma documental, o conhecimento de como planejar tipos recorrentes de Mission. Eles são escritos por quem conhece o processo da Alfa, em linguagem humana.

Isso **não** significa que a primeira versão do Planner lê Markdown. Significa que todo `PlanTemplate` que o Planner conhece precisa ter um Playbook correspondente que o justifique — nunca uma regra que exista só no código, sem alguém de negócio ter escrito por que ela é assim.

O Playbook é a fonte; o `PlanTemplate` é a tradução executável. Quando divergirem, o Playbook está certo e o código está desatualizado.

## O Planner tem o direito de não saber planejar

Um Engine que sempre produz um plano é um Engine que produz planos ruins quando não sabe. O Planner precisa poder dizer "entendi o pedido e não sei como executá-lo" — e isso precisa ser um evento de domínio de primeira classe, não uma exceção engolida nem um plano vazio.

Isso é diferente de `IntentRejected`, que pertence ao Intent Engine e significa "não entendi o pedido". Aqui o pedido foi entendido; o que falta é caminho.

## O que este Manifesto não decide

- A forma exata das entidades (`PLANNER_MODEL.md`).
- Como um `PlanTemplate` é escolhido a partir de uma Intent (`PLANNER_MODEL.md`/`PLANNER_LIFECYCLE.md`).
- Se e quando o Planner passa a consultar Memory/Knowledge, a ter estado, ou a decompor uma Intent em várias Missions — as três ficaram **fora** da Release 6 por decisão explícita do Product Owner, registradas como pendências, não como impossibilidades.

## Onde vive

`packages/planner-engine`, com as quatro camadas DDD do projeto. Sem dependência de código de `intent-engine` nem de `mission-engine` — apenas `core` e `kernel` ([ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md), [ADR-0092](docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md)).
