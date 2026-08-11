# Planner Model

As entidades do Planner Engine — o que existe, com quais atributos e relações. Escrito na Release 6A, depois de [PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md) e antes de qualquer código, seguindo [ADR-0082](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md).

O ciclo de vida (como uma decisão de planejamento acontece, passo a passo) está em [PLANNER_LIFECYCLE.md](PLANNER_LIFECYCLE.md). Os eventos, em [PLANNER_EVENTS.md](PLANNER_EVENTS.md).

## Onde o domínio do Planner começa — e onde termina

Começa quando uma **Intent estruturada** chega. Termina no instante em que um **Plan** é publicado (ou a impossibilidade de produzi-lo é publicada). Nada antes — interpretar linguagem natural é Intent Engine (Release 7). Nada depois — acompanhar execução é Mission Engine ([ADR-0089](docs/adr/0089-mission-nasce-do-plan-nao-da-intent.md)).

**O Planner não tem aggregate root persistido.** Ele é sem estado, por decisão registrada em [PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md#o-planner-é-sem-estado-e-isso-é-uma-decisão-de-desenho): recebe, decide, publica, esquece. Isso é a diferença estrutural em relação a Identity, Memory e Mission — e a razão pela qual este modelo tem Value Objects e um serviço de domínio, não entidades com identidade e histórico.

## As entidades

### Intent (entrada — Value Object)

O que o Planner recebe. **Não é o aggregate do Intent Engine** (Release 7) — é a forma de dado que o Planner exige na entrada, própria dele, pelo mesmo princípio que fez `Plan` ser Value Object do Mission Engine em [ADR-0092](docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md).

| Atributo | Tipo | Nota |
|---|---|---|
| `id` | `IntentId` | Identificador da Intent de origem |
| `correlationId` | `CorrelationId` | Correlação da cadeia inteira, vinda do Envelope de quem originou o pedido. **Atravessa o Planner sem ser alterada** e chega ao Mission Engine, que a exige em `Mission::create()`. Sem ela, o consumidor teria que inventar uma — e a rastreabilidade fim-a-fim se perderia exatamente na fronteira entre decidir e executar |
| `tenantId` | `TenantId` | Escopo obrigatório — multiempresa desde o schema |
| `workspaceId` | `WorkspaceId?` | Opcional, mesmo critério de [ADR-0093](docs/adr/0093-mission-workspace-opcional.md) |
| `objective` | `string` | O estado desejado, em linguagem natural. É o que [ADR-0037](docs/adr/0037-declarativo-nao-imperativo.md) chama de declarativo |
| `kind` | `IntentKind` | A classificação que permite escolher um `PlanTemplate` |
| `parameters` | `array<string, scalar>` | Dados já extraídos (ex: `clientId`, `system`) |
| `actor` | `Actor` | Quem originou — mesmo formato do campo `actor` do Envelope |
| `autonomyCeiling` | `int` (0–3) | O teto de autonomia de quem pediu, resolvido pelo Identity Engine |

`IntentKind` é um enum fechado nesta Release, com um valor por Playbook existente: `NovaReuniao`, `NovoCliente`, `NovoOrcamento`, `NovaObra`, `NovaImplantacao`, `NovaAcademia`, `NovoCondominio`. Fechado de propósito — um `kind` que o Planner não conhece precisa falhar visivelmente, não ser tratado genericamente.

### PlanTemplate (regra de planejamento)

A tradução executável de um [Playbook](playbooks/). É o conhecimento do Planner sobre como planejar um tipo recorrente de Mission.

| Atributo | Tipo | Nota |
|---|---|---|
| `kind` | `IntentKind` | O tipo de Intent que este template atende |
| `playbook` | `string` | Caminho do Playbook que o justifica (ex: `playbooks/nova-implantacao.md`) |
| `requiredParameters` | `list<string>` | Parâmetros sem os quais o plano não pode ser montado |
| `steps` | `list<PlanStep>` | As fases esperadas, em ordem |

**`playbook` é obrigatório e não decorativo**: nenhum `PlanTemplate` existe sem um Playbook que o justifique ([PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md#os-playbooks-são-a-matéria-prima-não-a-implementação)). Nesta Release os templates são definidos em código; o campo é o vínculo auditável com a fonte de negócio.

### PlanStep (uma fase do template)

| Atributo | Tipo | Nota |
|---|---|---|
| `description` | `string` | O que esta fase faz |
| `candidateAgent` | `string?` | Especialidade sugerida (ex: `claude`) — sugestão, não vínculo |
| `candidateCapability` | `string?` | Capability sugerida (ex: `gestor.budget.update`) |
| `requiredAutonomyLevel` | `int` (0–3) | **Resolvido aqui, pelo Planner** ([ADR-0029](docs/adr/0029-autonomia-progressiva.md)) |

O `requiredAutonomyLevel` alto é como um Playbook expressa "ponto de decisão humana": a Subtask correspondente vai gerar um `ApprovalGate` no Mission Engine quando exceder o `autonomyCeiling` da Mission. O Planner **não** cria gates — ele apenas marca o nível, e o Mission Engine decide, exatamente como `Mission::advanceToNextSubtask()` já faz desde a Release 5B.

### Plan (saída — Value Object)

O que o Planner produz. **Mesma forma de dado** do `Plan` do Mission Engine, deliberadamente **não** o mesmo código.

| Atributo | Tipo | Nota |
|---|---|---|
| `subtaskCandidates` | `list<SubtaskCandidate>` | Nunca vazio |
| `source` | `PlanSource` | Sempre `Planner` quando produzido aqui |

`SubtaskCandidate` tem exatamente os quatro campos que `packages/mission-engine` já define: `description`, `candidateAgent`, `candidateCapability`, `requiredAutonomyLevel`.

**Por que duplicar em vez de compartilhar via `packages/core`**: são dois bounded contexts distintos que trocam dados na fronteira, não um tipo compartilhado. Para o Planner, um `SubtaskCandidate` é *o que ele decidiu*; para o Mission Engine, é *o que ele recebeu e vai executar*. Compartilhar a classe acoplaria as duas evoluções — uma mudança que o Planner precisasse fazer passaria a exigir mudança no Mission Engine, exatamente o acoplamento que [ADR-0092](docs/adr/0092-plan-e-conceito-proprio-do-mission-engine.md) evitou ao recusar a dependência inversa. A tradução acontece uma vez, no payload do evento, e o [contrato](contracts/Planner.contract.yaml) é o que garante que as duas formas não divirjam.

Isso é diferente do caso `Identifier` (duplicada em três pacotes, pendência aberta): aquilo é encanamento sem significado de domínio; isto é fronteira de contexto.

### PlanningFailure (saída alternativa — Value Object)

O Planner tem o direito de não saber planejar ([PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md#o-planner-tem-o-direito-de-não-saber-planejar)).

| Atributo | Tipo | Nota |
|---|---|---|
| `intentId` | `IntentId` | A Intent que não pôde ser planejada |
| `reason` | `PlanningFailureReason` | Enum fechado |
| `detail` | `string` | Mensagem legível, nunca `null` |

`PlanningFailureReason`: `UnknownIntentKind` (nenhum `PlanTemplate` atende), `MissingRequiredParameter` (template encontrado, faltam dados), `EmptyPlan` (template produziu zero passos — bug de configuração, mas precisa falhar visivelmente).

### Planner (serviço de domínio)

Não é entidade e não tem identidade. É o serviço que recebe `Intent` e devolve `Plan` **ou** `PlanningFailure` — o único lugar do sistema onde essa decisão acontece ([ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md)).

## Relações

```
Intent ──(1:1)──> Plan            [caso feliz]
Intent ──(1:1)──> PlanningFailure [caso de falha]

PlanTemplate ──(1:1)──> IntentKind
PlanTemplate ──(1:N)──> PlanStep
PlanStep ────(1:1)────> SubtaskCandidate   [tradução em tempo de planejamento]
PlanTemplate ─(1:1)──> Playbook (documento, não código)
```

**Uma Intent gera exatamente uma Mission nesta Release.** [ADR-0028](docs/adr/0028-intencao-nao-comando.md) afirma que uma Intent *pode* decompor em várias Missions — capacidade adiada por decisão explícita do Product Owner, não negada. Quando chegar, o modelo ganha um conceito acima do `Plan` para agrupá-las; nenhuma decisão aqui a impede.

## O que este modelo não decide

- **Como o Planner escolhe entre dois templates do mesmo `kind`** — não existe esse caso nesta Release (1 template por `kind`, garantido pelo enum fechado).
- **Composição entre Playbooks** — o Playbook de Nova Implantação menciona "Playbook especializado aplicável, quando existir". Fora do escopo; um `PlanTemplate` é autocontido nesta Release.
- **Consulta a Memory/Knowledge antes de planejar** — o campo "Contexto necessário" dos Playbooks pede isso; adiado por decisão explícita. Consequência aceita: um `PlanTemplate` só pode usar os `parameters` que já vêm na Intent.
- **Replanejamento** — sem estado, sem histórico, sem replanejar. Ver [PLANNER_MANIFESTO.md](PLANNER_MANIFESTO.md#o-planner-é-sem-estado-e-isso-é-uma-decisão-de-desenho).
- **Uso de IA como apoio à decomposição** — permitido por [ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md), não implementado nesta Release.
- **Se `PlanTemplate` um dia é lido dos arquivos Markdown de `playbooks/`** — nesta Release são código; o campo `playbook` mantém o vínculo auditável para quando essa mudança for avaliada.

## Onde vive

`packages/planner-engine/src/Domain/`. As quatro camadas DDD do projeto; `Infrastructure/` **não terá persistência** — o Planner não guarda nada.
