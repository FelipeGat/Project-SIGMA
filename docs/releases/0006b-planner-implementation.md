# Release 6B — Planner Implementation

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 1 — aguardando aprovação do Product Owner.**

Implementa o que a [Release 6A](0006a-planner-research.md) modelou: [PLANNER_MANIFESTO.md](../../PLANNER_MANIFESTO.md), [PLANNER_MODEL.md](../../PLANNER_MODEL.md), [PLANNER_LIFECYCLE.md](../../PLANNER_LIFECYCLE.md), [PLANNER_EVENTS.md](../../PLANNER_EVENTS.md), [contracts/Planner.contract.yaml](../../contracts/Planner.contract.yaml) e as ADRs [0095](../adr/0095-planner-sem-estado.md)/[0096](../adr/0096-plan-duplicado-por-bounded-context.md)/[0097](../adr/0097-planner-falha-visivelmente.md) — todas já aprovadas.

## Objetivo

Fazer o SIGMA **decidir** pela primeira vez.

Até aqui o sistema guarda (Identity, Memory) e acompanha (Mission), mas nada nele transforma um objetivo declarado em passos. A Release 5.5 provou o caminho ponta a ponta com um `Plan` que o próprio usuário escreveu no corpo da requisição — o que funciona, mas é exatamente o oposto da promessa declarativa do [MANIFESTO.md](../../MANIFESTO.md).

Ao fim desta Release, uma Intent com `kind: nova_implantacao` e os parâmetros exigidos produz um `Plan` real, publica `mission.planned`, e o Mission Engine cria a Mission correspondente **sem que ninguém tenha escrito os passos**.

## Escopo

**Existe** — quatro entregas:

1. **`packages/planner-engine/src/Domain/`** — os Value Objects de [PLANNER_MODEL.md](../../PLANNER_MODEL.md): `Intent`, `IntentKind`, `PlanTemplate`, `PlanStep`, `Plan`, `PlanSource`, `SubtaskCandidate`, `PlanningFailure`, `PlanningFailureReason`, `Actor`, os identificadores como Value Objects ([ADR-0063](../adr/0063-identificadores-como-value-objects.md)), os dois eventos, e o serviço de domínio `Planner`.

2. **`PlanTemplateRegistry` com os sete templates**, um por Playbook existente. Cada um traduz as "Fases esperadas" do seu Playbook em `PlanStep`s, com `requiredAutonomyLevel` refletindo os "Pontos de decisão humana" documentados. **Onde um Playbook estiver incompleto demais para produzir passos honestos, o template declara apenas o que o Playbook de fato diz** — nunca passos inventados para preencher.

3. **`packages/planner-engine/src/Application/`** — um caso de uso, `PlanFromIntent`, que executa os quatro passos de [PLANNER_LIFECYCLE.md](../../PLANNER_LIFECYCLE.md) e publica `mission.planned` **ou** `planning.failed` via `IEventBus`. **Sem `Infrastructure/` de persistência** (ADR-0095).

4. **`Interfaces/PlannerEngineModule`** — o Module que registra o caso de uso no container, seguindo o padrão de `MissionEngineModule`. Sem migrations: não há banco.

**Não existe** — explicitamente fora do escopo:

- **Consumidor real do `mission.planned`.** O Mission Engine ainda não assina nada (constatado na Release 5C — "nenhum evento é assinado"). Fazê-lo assinar é a entrega natural da Release 6C, não desta. Ver Risco 1.
- **Superfície HTTP ou worker.** O caso de uso existe e é testável; quem o invoca fica para a 6C.
- **Tudo que ADR-0095/0096/0097 e o [Decision Log 6A](0006a-planner-research-decision-log.md) já registraram como fora**: estado, replanejamento, múltiplas Missions por Intent, consulta a Memory/Knowledge, composição de Playbooks, templates lidos de Markdown, IA como apoio.
- **Nenhuma mudança em `packages/mission-engine`** (ADR-0096).

## Arquitetura

`packages/planner-engine` depende de `core` e `kernel` apenas — nunca de `intent-engine` nem de `mission-engine` ([ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md), [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md)). Três camadas DDD em vez de quatro: `Domain/`, `Application/`, `Interfaces/` — `Infrastructure/` não existe porque não há o que persistir.

É o primeiro Engine do projeto sem `Infrastructure/`, e o primeiro cujo "estado" é inteiramente o argumento da chamada.

## Dependências

- Release 6A completa e aprovada (documentos e ADRs) — **feito**.
- Release 5 completa (o `Plan` do Mission Engine é a forma que este contrato precisa espelhar) — **feito**.
- Redis de pé para os testes de publicação — já parte do ambiente.

## Riscos

1. **O maior: nada consome `mission.planned`.** Ao fim desta Release o Planner publica corretamente e nenhuma Mission nasce disso — o `Plan` se perde. Isso é esperado e está no escopo declarado, mas precisa ficar explícito no Validation Report para não ser lido como funcionalidade entregue. Agravado pela ausência de replay no Redis pub/sub (Release 4.5). **A Release 6C — fazer o Mission Engine assinar `mission.planned` — é o que fecha o ciclo.**

2. **Os sete Playbooks estão incompletos, e traduzi-los pode virar invenção.** É o risco mais insidioso desta Release: preencher lacunas de conhecimento de negócio com suposições plausíveis produziria um Planner que decide errado com confiança — exatamente o que [ADR-0097](../adr/0097-planner-falha-visivelmente.md) combate. **Mitigação**: um `PlanStep` só existe se o Playbook o descreve; o Decision Log lista, por template, o que o Playbook não respondeu; e um Playbook vago demais gera um template com poucos passos, não passos inventados.

3. **As duas formas de `Plan` podem divergir** (ADR-0096, custo aceito). Mitigação: teste automatizado comparando a estrutura produzida aqui com a que `packages/mission-engine` aceita, e verificação explícita na Architecture Validation.

4. **`requiredAutonomyLevel` mal calibrado nos templates** faria uma Mission pedir aprovação humana onde não precisa (irritante) ou executar sem pedir onde precisa (perigoso). Mitigação: o nível vem dos "Pontos de decisão humana" do Playbook, não de julgamento próprio; onde o Playbook não diz, o template usa o nível mais restritivo.

## Entregáveis

- `packages/planner-engine/` com `Domain/`, `Application/`, `Interfaces/`, `composer.json`, `VERSION.md`.
- Testes automatizados dos quatro passos do ciclo de vida, das três causas de falha, dos sete templates e da igualdade de forma do `Plan`.
- `system-manifest.yaml` com o Module `planner-engine`.
- **Decision Log** (`0006b-planner-implementation-decision-log.md`) — incluindo, por template, o que o Playbook não respondeu.
- **Validation Report** (`0006b-planner-implementation-validation-report.md`).
- `ROADMAP.md`/`CHANGELOG.md`/`memory/STATE.md`/`memory/NEXT.md`/`packages/README.md` atualizados.

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

**Automatizados** — o caminho feliz por `IntentKind` (sete); as três causas de `PlanningFailed`, incluindo a ordem de verificação; `Plan` nunca vazio; `source` sempre `planner`; a igualdade de forma com o `SubtaskCandidate` do Mission Engine; publicação dos dois eventos via `InMemoryEventBus`. Os 268 testes atuais permanecem verdes.

**Architecture Validation** — `composer.lock` de `planner-engine` não lista nenhum Engine; nenhum `use` resolve para `mission-engine`/`intent-engine`; não existe `Infrastructure/` com banco; todo `PlanTemplate` referencia um Playbook que existe no repositório (verificável por script).

**Scenario Validation** — contra Redis real: uma Intent `nova_implantacao` completa produz `mission.planned` capturável via `redis-cli psubscribe`, com payload conforme o contrato; uma Intent de `kind` desconhecido produz `planning.failed`; o payload de `mission.planned` é aceito por `CreateMission` do Mission Engine **sem adaptação** — a prova prática de ADR-0096.

## Critérios de Aceite

1. Uma Intent estruturada de cada um dos sete `IntentKind` produz um `Plan` não vazio, com `source: planner`.
2. As três causas de `PlanningFailed` disparam corretamente e **na ordem** especificada.
3. `mission.planned` é capturado no Redis real com payload conforme o contrato, e seu campo `plan` é aceito por `CreateMission` sem adaptação.
4. Nenhum `PlanStep` existe sem estar descrito no Playbook correspondente — verificado na revisão e registrado no Decision Log.
5. `planner-engine` não depende de nenhum Engine; não tem persistência.
6. Suíte completa verde, sem regressão.
7. Decision Log e Validation Report publicados, o primeiro listando o que cada Playbook não respondeu.
