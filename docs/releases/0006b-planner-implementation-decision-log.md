# Release 6B — Planner Implementation — Decision Log

Decisões locais tomadas durante a Implementation, dentro do escopo aprovado na [Proposal 6B](0006b-planner-implementation.md) — ver [ADR-0047](../adr/0047-decision-log-por-release.md).

## O que cada Playbook não respondeu

O Critério de Aceite 6 exige isto template a template. **Nenhum `PlanStep` foi criado sem estar descrito no Playbook correspondente** — onde o Playbook cala, o template cala junto.

**A lacuna transversal, presente nos sete**: nenhum Playbook nomeia **parâmetros**. O campo "Contexto necessário" é prosa (*"Identidade do cliente/Project envolvido"*), não uma lista de campos. Os `requiredParameters` de todos os templates foram **derivados dessa prosa** — é o ponto mais frágil desta Release, e merece revisão de quem é dono dos Playbooks. Um nome errado aqui produz `PlanningFailed` em Intents legítimas.

**Segunda lacuna transversal**: nenhum Playbook nomeia **Capabilities**. Eles nomeiam *Skills* (`GestorSkill`, `GoogleCalendarSkill`), que não são a mesma coisa ([ADR-0027](../adr/0027-capability-unidade-de-skill.md)), e o Capability Registry é a Release 18. Por isso **`candidateCapability` é `null` em todos os 32 passos** — há teste automatizado garantindo que continue assim até haver de onde tirar o valor.

**Terceira**: a seção "Agentes e Skills tipicamente envolvidos" lista Agents para o Playbook inteiro, não por fase. `candidateAgent` só foi preenchido onde a **própria fase** nomeia um: `manus` (ata, nova-reuniao fase 4), `gemini` (peça visual, novo-orcamento fase 3), `claude` (migração/configuração técnica, nova-academia e novo-condominio). Nos outros 26 passos é `null` — atribuir por inferência seria decidir por quem escreveu o Playbook.

| Template | Passos | O que o Playbook não respondeu |
|---|---|---|
| `nova_reuniao` | 5 | Quem é "Agent e Skill de captura" da fase 3 — o próprio Playbook diz "não presumido no Sprint 0.1". A fase foi mantida sem agente. |
| `novo_cliente` | 4 | Quais são os "dados básicos" exigidos (razão social? CNPJ? contato?). Derivados: `razaoSocial`, `produto`. |
| `novo_orcamento` | 5 | O que é "quando aplicável" na peça visual — a fase existe sempre no template, porque o Playbook não dá o critério. |
| `nova_obra` | 4 | A fase 4 é declarada pelo próprio Playbook como "ponto de integração com Missions subsequentes, não coberto em detalhe". Mantida com a redação dele, sem detalhamento inventado. |
| `nova_implantacao` | 4 | O "Playbook especializado aplicável, quando existir" (composição) — fora de escopo por decisão da 6A. E onde termina "provisionar" e começa "ativar em produção", que é o que o gate cobre. |
| `nova_academia` | 5 | O critério de "quando aplicável" para a migração de base; e a Skill do AlfaGym, que o próprio Playbook diz não estar documentada em `/skills`. |
| `novo_condominio` | 5 | Mesmas duas do anterior, para o AlfaControl. |

## `autonomyCeiling` fixo em `0` no gateway — a decisão mais desconfortável

`POST /intents` monta a Intent com `autonomyCeiling: 0` (Consultivo), **fixo**. O Identity Engine não expõe o nível de autonomia configurado por User/Role — a divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) está aberta desde a Release 3B e o [ADR-0068](../adr/0068-autonomy-por-capability.md) manda reconciliá-la até o Skill Engine.

Assumir um valor mais alto faria Subtasks sensíveis — provisionar ambiente em produção, migrar base de alunos — **executarem sem o gate que o Playbook exige**. Entre errar para o lado que gera aprovação a mais e o que executa sem pedir, esta Release erra para o primeiro.

Consequência prática, visível na validação: toda Mission criada por `POST /intents` vai parar em `PendingApproval` no primeiro passo de nível ≥ 1. É o comportamento correto dado o que se sabe hoje, e é pendência real.

## `202`, não `201` — e `422`, não `400`

`POST /intents` responde **`202 Accepted`**: a Mission não existe nesse instante. Responder `201` exigiria o gateway esperar o worker, reintroduzindo acoplamento síncrono entre Engines ([ADR-0008](../adr/0008-arquitetura-orientada-a-eventos.md)).

Planejamento impossível responde **`422`**, não `400`: a requisição estava bem formada e foi aceita; o sistema é que reconhece não saber executá-la ([ADR-0097](../adr/0097-planner-falha-visivelmente.md)). Já um `kind` fora do enum é `400` — requisição malformada, e o Planner **nem chega a ser chamado**, por isso nada é publicado nesse caso. A distinção está coberta por teste.

## `PlanTemplateRegistry` ganhou injeção pelo construtor

Os sete templates reais são corretos — e por isso nunca produzem `UnknownIntentKind` nem `EmptyPlan`, que são justamente dois dos três desfechos de [ADR-0097](../adr/0097-planner-falha-visivelmente.md) que precisam de teste.

Alternativa descartada: remover o `final` da classe para os testes a estenderem. Preferido: um parâmetro opcional de construtor, `null` em produção. Afrouxar a classe inteira para viabilizar teste custaria mais do que um parâmetro documentado.

## O handler é registrado no worker, não no Module

`MissionEngineModule::register()` continua apenas bindando casos de uso. Quem liga `mission.planned` a `CreateMissionFromPlannedEvent` é `services/mission-worker/bin/worker.php`, explicitamente.

O Engine não deve saber que existe um processo consumindo seus eventos — essa amarração é da borda. É o mesmo raciocínio que mantém o Planner sem conhecer o Mission, e difere do `MemoryEngineModule`, que assina dentro do próprio Module (Release 4B). Divergência consciente entre os dois workers, registrada aqui.

## Um payload inválido não derruba o loop

O handler do worker captura `Throwable`, registra em `STDERR` e segue. A mensagem problemática é **perdida** — sem retry, sem dead-letter. Mesma limitação já aceita no `memory-worker`, agravada por não haver replay no Redis pub/sub.

Alternativa descartada: deixar a exceção subir e o processo morrer, contando com `restart: on-failure`. Isso transformaria um payload malformado num loop de reinício, e o container passaria a perder **todos** os eventos seguintes, não só o defeituoso.

## `CreateMissionFromPlannedEvent` valida `plan.source` em vez de assumir

Um Plan que chegue por esse canal marcado como `manual` é erro de quem publicou. O caso de uso lê o campo do payload e rejeita valor desconhecido, em vez de assumir `planner` — o consumidor não deveria corrigir silenciosamente o produtor.

## `Identifier` foi duplicada pela quarta vez

`packages/planner-engine/src/Domain/Identifier.php` é cópia de identity/memory/mission. A consolidação em `packages/core` segue recomendada e não decidida há quatro Releases, e esta Implementation não se acopla a essa pendência — mesmo precedente de 4A e 5B.

**Não confundir com [ADR-0096](../adr/0096-plan-duplicado-por-bounded-context.md)**: aquela decidiu duplicar `Plan` por ser fronteira de bounded context, o que é desenho. Esta é dívida.

## Achado: `packages/README.md` estava certo por acaso

Ele já listava `planner-engine → core, kernel`, o que virou verdade nesta Release. Mas listava porque a coluna foi corrigida na Release 5A por causa de outro bug — não porque alguém tivesse decidido a dependência do Planner. A afirmação estava certa antes de haver código que a sustentasse.
