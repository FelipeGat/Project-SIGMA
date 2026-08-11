# Release 6B — Planner Implementation (ciclo fechado)

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 2 — aguardando aprovação do Product Owner.**

> **O que mudou da revisão 1**: o Product Owner decidiu **unir a 6B e a 6C numa Release só**. A revisão 1 entregava um Planner que publicava `mission.planned` sem que ninguém consumisse — o Plan se perdia, e isso era o Risco 1 da própria proposta. Esta revisão absorve o consumo: ao fim da Release, uma Intent vira uma Mission real, sozinha.

Implementa o que a [Release 6A](0006a-planner-research.md) modelou — [PLANNER_MANIFESTO.md](../../PLANNER_MANIFESTO.md), [PLANNER_MODEL.md](../../PLANNER_MODEL.md), [PLANNER_LIFECYCLE.md](../../PLANNER_LIFECYCLE.md), [PLANNER_EVENTS.md](../../PLANNER_EVENTS.md), [contracts/Planner.contract.yaml](../../contracts/Planner.contract.yaml) e as ADRs [0095](../adr/0095-planner-sem-estado.md)/[0096](../adr/0096-plan-duplicado-por-bounded-context.md)/[0097](../adr/0097-planner-falha-visivelmente.md), todas aprovadas.

## Objetivo

Fazer o SIGMA **decidir** pela primeira vez — e executar a decisão até virar uma Mission, sem ninguém escrever os passos.

A Release 5.5 provou o caminho ponta a ponta com um `Plan` que o próprio usuário escreveu no corpo da requisição. Funciona, mas é o oposto da promessa declarativa do [MANIFESTO.md](../../MANIFESTO.md): quem pede ainda especifica o como.

Ao fim desta Release, o fluxo é:

```
POST /intents  →  Planner monta o Plan  →  mission.planned (Redis)
                                                  │
                                    mission-worker consome
                                                  │
                                          Mission criada
                                                  ▼
                                      GET /missions/{id}  →  200
```

O usuário descreve **o quê**. O SIGMA decide **o como**. É a primeira vez que a cadeia `Intent → Planner → Mission` de [ARCHITECTURE.md §3](../architecture/ARCHITECTURE.md) roda de verdade.

## Escopo

**Existe** — seis entregas:

1. **`packages/planner-engine/src/Domain/`** — os Value Objects de [PLANNER_MODEL.md](../../PLANNER_MODEL.md): `Intent`, `IntentKind`, `PlanTemplate`, `PlanStep`, `Plan`, `PlanSource`, `SubtaskCandidate`, `PlanningFailure`, `PlanningFailureReason`, `Actor`, os identificadores como Value Objects ([ADR-0063](../adr/0063-identificadores-como-value-objects.md)), os dois eventos e o serviço de domínio `Planner`.

2. **`PlanTemplateRegistry` com os sete templates**, um por Playbook. Cada um traduz as "Fases esperadas" do seu Playbook em `PlanStep`s, com `requiredAutonomyLevel` refletindo os "Pontos de decisão humana" documentados. **Onde o Playbook for vago, o template declara só o que ele de fato diz** — nunca passos inventados (ver Risco 2).

3. **`packages/planner-engine/src/Application/`** — o caso de uso `PlanFromIntent`, executando os quatro passos de [PLANNER_LIFECYCLE.md](../../PLANNER_LIFECYCLE.md) e publicando `mission.planned` **ou** `planning.failed`. **Sem `Infrastructure/` de persistência** ([ADR-0095](../adr/0095-planner-sem-estado.md)) — três camadas DDD, não quatro.

4. **`POST /intents` em `services/gateway`** — a porta de entrada. Recebe `objective`, `kind` e `parameters`; resolve `tenantId`/`workspaceId`/`actor`/`autonomyCeiling` da Session (nunca do corpo, mesma disciplina de `POST /missions` na 5.5); invoca `PlanFromIntent`. Responde `202` com o `intentId` e o `correlationId` — **não** com a Mission, que ainda não existe nesse instante.

5. **`CreateMissionFromPlannedEvent` em `packages/mission-engine/src/Application/`** — o caso de uso que traduz o payload de `mission.planned` na chamada a `CreateMission`. É a primeira vez que o Mission Engine **consome** um evento; até a 5C ele só publicava.

6. **`services/mission-worker`** — processo sem HTTP que assina `mission.planned` via `RedisSubscriber` e invoca o caso de uso acima. Mesmo padrão de `services/memory-worker` (Release 4B), incluindo `read_write_timeout: -1` e `restart: on-failure`. Entra no `docker-compose.yml` **sem** `healthcheck` (exceção de [ADR-0094](../adr/0094-health-endpoints-pertencem-ao-kernel.md): não escuta HTTP).

**Não existe** — explicitamente fora do escopo:

- **Consumidor de `planning.failed`.** Publicado corretamente, ninguém escuta. Audit Engine (Release 11) é o natural.
- **Intent Engine.** A Intent chega estruturada, com `kind` explícito no corpo da requisição — ninguém interpreta linguagem natural ainda (Release 7).
- **Tudo que ADR-0095/0096/0097 e o [Decision Log 6A](0006a-planner-research-decision-log.md) já registram como fora**: estado, replanejamento, múltiplas Missions por Intent, consulta a Memory/Knowledge, composição de Playbooks, templates lidos de Markdown, IA como apoio.
- **Alteração no `Domain/` do Mission Engine.** A entrega 5 é `Application/` nova; nenhum aggregate muda ([ADR-0096](../adr/0096-plan-duplicado-por-bounded-context.md)).

## Arquitetura

`packages/planner-engine` depende de `core` e `kernel` apenas ([ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md), [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md)) — **nunca** de `mission-engine`, mesmo agora que os dois participam do mesmo fluxo. O acoplamento é o payload do evento, e o [contrato](../../contracts/Planner.contract.yaml) é onde a igualdade das duas formas de `Plan` é verificável.

`services/mission-worker` é quem conhece os dois lados — e é um *service*, não um Engine. É o mesmo papel que `services/memory-worker` já exerce entre Identity e Memory: a tradução entre bounded contexts vive na borda, nunca dentro de um Engine.

`services/gateway` passa a registrar cinco Modules (`kernel`, `event-bus`, `identity-engine`, `mission-engine`, `planner-engine`).

**Sobre o `202`**: `POST /intents` responde antes de a Mission existir, porque a criação é assíncrona por desenho ([ADR-0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md) — o Planner publica e esquece). Responder `201` com a Mission exigiria o gateway esperar o worker, reintroduzindo acoplamento síncrono entre Engines que [ADR-0008](../adr/0008-arquitetura-orientada-a-eventos.md) proíbe.

## Dependências

- Release 6A completa e aprovada — **feito**.
- Release 5 completa (`CreateMission` é o que o worker invoca) — **feito**.
- `RedisSubscriber` (`services/event-bus`, Release 4B) — **feito**, reusado sem alteração.

## Riscos

1. **Os sete Playbooks estão incompletos, e traduzi-los pode virar invenção.** É o risco mais insidioso: preencher lacunas de conhecimento de negócio com suposições plausíveis produziria um Planner que decide errado com confiança — exatamente o que [ADR-0097](../adr/0097-planner-falha-visivelmente.md) combate. **Mitigação**: um `PlanStep` só existe se o Playbook o descreve; o Decision Log lista, template a template, o que o Playbook **não** respondeu; um Playbook vago gera template com poucos passos, nunca passos inventados.

2. **Perda de evento durante queda do worker é definitiva.** Redis pub/sub não tem replay — confirmado com evidência real na [Release 4.5](0004.5-platform-validation-validation-report.md). Uma Intent planejada enquanto o `mission-worker` estiver fora do ar **não vira Mission, e ninguém percebe**: o Planner é sem estado e o usuário já recebeu `202`. É a consequência mais séria desta Release e precisa estar explícita no Validation Report. Mitigação parcial: `restart: on-failure`. Mitigação real (Redis Streams) continua fora de escopo.

3. **As duas formas de `Plan` podem divergir** ([ADR-0096](../adr/0096-plan-duplicado-por-bounded-context.md), custo aceito). Mitigação: teste que leva o payload publicado pelo Planner até `CreateMission` **sem adaptação** — se divergirem, esse teste quebra.

4. **`requiredAutonomyLevel` mal calibrado** faria uma Mission pedir aprovação humana onde não precisa (irritante) ou executar sem pedir onde precisa (perigoso). Mitigação: o nível vem dos "Pontos de decisão humana" do Playbook; onde o Playbook não diz, usa-se o nível mais restritivo.

5. **Release grande** — dois pacotes, um service novo, uma rota nova. Mitigação: a ordem das entregas (1→6) é executável incrementalmente, e cada uma tem teste próprio antes da seguinte.

## Entregáveis

- `packages/planner-engine/` (`Domain/`, `Application/`, `Interfaces/`, `composer.json`, `VERSION.md`).
- `packages/mission-engine/src/Application/UseCase/CreateMissionFromPlannedEvent.php`.
- `services/mission-worker/` (`Bootstrap.php`, `bin/worker.php`, `composer.json`), `docker/mission-worker.Dockerfile`, serviço no `docker-compose.yml`.
- `services/gateway` com `POST /intents`.
- `system-manifest.yaml` com o Module `planner-engine`.
- **Decision Log** — incluindo, template a template, o que o Playbook não respondeu.
- **Validation Report**.
- `ROADMAP.md`/`CHANGELOG.md`/`memory/STATE.md`/`memory/NEXT.md`/`packages/README.md` atualizados.

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

**Automatizados** — caminho feliz por `IntentKind` (sete); as três causas de `PlanningFailed`, incluindo a ordem de verificação; `Plan` nunca vazio; `source` sempre `planner`; `correlationId` preservado da Intent até o payload; `POST /intents` (sucesso, sem token, `kind` inválido, parâmetro faltando); `CreateMissionFromPlannedEvent` com payload real. Os 268 testes atuais permanecem verdes.

**Architecture Validation** — `composer.lock` de `planner-engine` não lista nenhum Engine; nenhum `use` resolve para `mission-engine`/`intent-engine`; não existe `Infrastructure/` com banco em `planner-engine`; todo `PlanTemplate` referencia um Playbook que existe no repositório (verificável por script).

**Scenario Validation** — contra `docker compose up --build` real:

1. `POST /intents` com `kind: nova_implantacao` e parâmetros completos → `202`.
2. `mission.planned` capturado no Redis com payload conforme o contrato.
3. `docker compose ps` mostra `mission-worker` de pé; o log confirma o consumo.
4. A Mission aparece no MariaDB, e `GET /missions/{id}` responde `200` com `plan.source: planner` e as Subtasks candidatas do Playbook.
5. **O `correlationId` do `202` é o mesmo da Mission criada** — a prova de que a rastreabilidade sobrevive à fronteira assíncrona.
6. `POST /intents` com `kind` desconhecido → `planning.failed` no Redis, nenhuma Mission criada.
7. Com o `mission-worker` parado: `POST /intents` responde `202`, nenhuma Mission nasce, e o evento **se perde** — Risco 2 demonstrado, não escondido.
8. O caminho da Release 5.5 (`POST /missions` manual) continua funcionando.

## Critérios de Aceite

1. Uma Intent de cada um dos sete `IntentKind` produz um `Plan` não vazio, `source: planner`.
2. As três causas de `PlanningFailed` disparam corretamente e **na ordem** especificada.
3. **O ciclo fecha**: `POST /intents` → Mission consultável por `GET /missions/{id}`, contra Docker real, sem ninguém escrever os passos.
4. O `correlationId` atravessa toda a cadeia sem mudar.
5. O payload de `mission.planned` é aceito por `CreateMission` **sem adaptação**.
6. Nenhum `PlanStep` existe sem estar descrito no Playbook correspondente — registrado template a template no Decision Log.
7. `planner-engine` não depende de nenhum Engine e não tem persistência.
8. O cenário 7 (worker parado) executado e documentado, com a perda registrada como limitação real.
9. Suíte completa verde, sem regressão.
10. Decision Log e Validation Report publicados.
