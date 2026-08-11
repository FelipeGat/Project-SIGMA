# Release 6A — Planner Research — Decision Log

Decisões tomadas durante a etapa de modelagem do Planner Engine, dentro do escopo de [ADR-0082](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) — ver [ADR-0047](../adr/0047-decision-log-por-release.md). O levantamento que as precedeu está em [Planner Research](0006a-planner-research.md).

## As seis perguntas e como foram respondidas

O documento de Research listou seis perguntas em aberto. O Product Owner aprovou a recomendação apresentada para as três de maior impacto e delegou as demais. Cada uma virou decisão registrada:

| # | Pergunta | Resposta | Onde |
|---|---|---|---|
| 3 | O Planner tem estado persistido? | **Não** | [ADR-0095](../adr/0095-planner-sem-estado.md) |
| 4 | Uma Intent gera quantas Missions? | **Uma**, nesta Release | [PLANNER_MODEL.md](../../PLANNER_MODEL.md#relações) |
| 5 | O Planner consulta Memory/Knowledge? | **Não** | [PLANNER_LIFECYCLE.md](../../PLANNER_LIFECYCLE.md#passo-2--validar-parâmetros) |
| 1 | De quem é o `Plan`? | **De cada Engine, duplicado por fronteira de contexto** | [ADR-0096](../adr/0096-plan-duplicado-por-bounded-context.md) |
| 2 | Como o Planner planeja na v1? | **`PlanTemplate` em código, vinculado a um Playbook** | [PLANNER_MODEL.md](../../PLANNER_MODEL.md#plantemplate-regra-de-planejamento) |
| 6 | Evento para "não sei planejar"? | **`PlanningFailed` (`planning.failed`), novo** | [ADR-0097](../adr/0097-planner-falha-visivelmente.md) |

## Por que a pergunta 1 não seguiu o instinto de "não duplicar"

A resposta mais confortável seria mover `Plan`/`SubtaskCandidate` para `packages/core` — o projeto já lamenta a duplicação de `Identifier` em três pacotes, e duplicar mais uma coisa parece repetir um erro reconhecido.

Foi rejeitada porque os dois casos não são o mesmo problema. `Identifier` é encanamento sem significado de domínio; `Plan` é fronteira entre bounded contexts. Compartilhar a classe acoplaria as evoluções dos dois Engines — o mesmo acoplamento que [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md) recusou, apenas na direção oposta.

O custo é real e está registrado: nada no compilador impede que as duas formas divirjam. A Architecture Validation da Release 6B precisa verificar a igualdade explicitamente, e o Validation Report precisa dizer que essa é uma garantia de processo, não estrutural.

## `IntentKind` é enum fechado, e isso é o mecanismo de ADR-0097

Um `IntentKind` aberto (string livre) tornaria impossível distinguir "tipo que o Planner não conhece" de "tipo escrito errado", e empurraria naturalmente para um template genérico de fallback. Fechá-lo é o que torna `unknown_intent_kind` um sinal preciso.

Os sete valores correspondem exatamente aos sete Playbooks existentes. Um Playbook novo passa a exigir um valor novo no enum — acoplamento deliberado entre a documentação de negócio e o código.

## `PlanTemplate.playbook` é obrigatório

Poderia ser opcional, ou não existir. É obrigatório para tornar auditável a regra do [PLANNER_MANIFESTO.md](../../PLANNER_MANIFESTO.md#os-playbooks-são-a-matéria-prima-não-a-implementação): nenhuma regra de planejamento existe sem alguém de negócio ter escrito por que ela é assim.

Nesta Release os templates são código, então o campo é apenas um caminho de arquivo — não é lido nem validado em runtime. É um vínculo documental verificável por revisão, e o Validation Report deve tratá-lo como tal, não como garantia automática.

## O Planner marca o nível de autonomia, mas nunca o compara

`PlanStep.requiredAutonomyLevel` é resolvido pelo Planner ([ADR-0029](../adr/0029-autonomia-progressiva.md)). A comparação contra o `autonomyCeiling`, e a criação do `ApprovalGate`, continuam sendo do Mission Engine — `Mission::advanceToNextSubtask()`, implementado na Release 5B.

Duplicar a comparação no Planner criaria duas fontes de verdade sobre quando um gate humano nasce. `Intent.autonomyCeiling` existe no modelo porque precisa viajar no payload até o Mission Engine, não porque o Planner o use para decidir.

## As três causas de falha são verificadas em ordem

`UnknownIntentKind` → `MissingRequiredParameter` → `EmptyPlan`, e a primeira que dispara encerra. Uma Intent de `kind` desconhecido com parâmetros faltando reporta a primeira. Reportar a falha mais externa é o que dá uma mensagem acionável a quem for diagnosticar.

## O achado documental desta rodada

`packages/planner-engine/README.md` afirmava depender de `packages/intent-engine`. A [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md) registra ter corrigido exatamente essa afirmação "na mesma rodada" — corrigiu em `packages/README.md` e passou batido no README do próprio pacote. Corrigido, com nota explicando a contradição para quem ler o histórico.

## O que ficou explicitamente de fora

Cada item abaixo é pendência registrada, não impossibilidade — e nenhuma decisão desta Release impede que chegue depois:

- Estado persistido e replanejamento (ADR-0095).
- Uma Intent decompondo em múltiplas Missions — [ADR-0028](../adr/0028-intencao-nao-comando.md) prevê, adiado.
- Consulta a Memory/Knowledge antes de planejar. **Consequência concreta**: um Playbook cujo "Contexto necessário" só existe fora da Intent não pode virar `PlanTemplate` nesta Release.
- Composição entre Playbooks (o "especializado, quando existir" de `nova-implantacao.md`).
- `PlanTemplate` lido dos arquivos Markdown.
- IA como apoio à decomposição — permitido por [ADR-0012](../adr/0012-planner-decide-nunca-a-ia.md), não implementado.
- Metadata padrão em eventos ([ADR-0076](../adr/0076-metadata-padrao-em-eventos-de-dominio.md)) — aprovada como direção, nenhum Engine a implementou; o Planner não abre esse precedente sozinho.
