# SIGMA Protocol

**O documento de maior autoridade técnica do projeto.** [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) descreve a topologia do SIGMA — quem fala com quem. Este documento descreve a língua — o que é dito, em que formato, sob quais regras. Nenhum Engine, Plugin ou Agent implementado a partir da Release 2 pode se desviar do que está definido aqui sem que isso seja, primeiro, uma mudança neste documento. Ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md).

## Por que este documento vem antes de qualquer Engine

Já sabemos que Client, Project, Company, Skill, Agent, Mission, Memory e Workspace existem como conceitos (ver [DOMAIN.md](DOMAIN.md)). O que não estava definido era **como eles conversam**: o formato de uma resposta, como uma Mission muda de estado, como um Agent recebe contexto, como uma integração registra auditoria. Construir Engines sem esse contrato definido primeiro arrisca cada um inventar seu próprio formato — o retrabalho de integração entre nove Engines desalinhados é maior do que o custo de definir o protocolo uma vez, cedo. Ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md).

---

## 1. O Envelope

Toda resposta produzida por uma Capability, por um Agent, ou por qualquer canal externo (Telegram, GitHub, Claude, ChatGPT, Gemini, Manus — sem exceção) é normalizada neste formato antes de retornar ao chamador:

```json
{
  "protocolVersion": "1.0",
  "correlationId": "",
  "requestId": "",
  "timestamp": "",
  "missionId": null,
  "workspaceId": null,
  "actor": { "type": "user", "id": "" },
  "intent": { "id": "", "objective": "" },
  "capability": { "name": "", "skill": "", "plugin": "" },
  "success": true,
  "data": null,
  "events": [],
  "memory": [],
  "warnings": [],
  "error": null,
  "audit": { "riskLevel": "read", "autonomyLevelRequired": 0, "autonomyLevelEffective": 0 },
  "nextActions": []
}
```

| Campo | Tipo | Significado |
|---|---|---|
| `protocolVersion` | string | Versão do SIGMA Protocol que produziu esta resposta — ver [§10](#10-versionamento-do-protocolo) |
| `correlationId` | string | Identificador estável de toda a cadeia que nasce de uma mesma Intent — o mesmo valor atravessa todas as Missions/Subtasks/chamadas relacionadas. É o que substitui a necessidade de repetir logs no envelope (ver `audit` abaixo): consulta-se o histórico completo no Audit Engine por este identificador |
| `requestId` | string | Identificador desta chamada específica — único por invocação, mesmo dentro do mesmo `correlationId` |
| `timestamp` | string (ISO 8601) | Momento em que a resposta foi produzida |
| `missionId` | string \| null | Identificador da Mission de origem, quando a chamada acontece no contexto de uma |
| `workspaceId` | string \| null | Identificador do Workspace de origem, quando aplicável (ver [WORKSPACES.md](WORKSPACES.md)) |
| `actor` | object | Quem originou a cadeia: `{ type: "user"\|"system"\|"agent", id }` |
| `intent` | object | Referência à Intent de origem: `{ id, objective }` — `objective` é a frase-objetivo em linguagem natural/SGL (ver [§2](#2-intenção-não-comando) e [SGL.md](SGL.md)) |
| `capability` | object | Qual Capability foi invocada: `{ name, skill, plugin }` — ausente em respostas que não passam por uma Capability (ex: resposta de um Agent que só raciocina, sem agir) |
| `success` | boolean | Se a chamada foi concluída sem falha |
| `data` | object \| null | O resultado de negócio da chamada (ex: o `Meeting` criado por `CreateEvent`) |
| `events` | array | Eventos publicados como consequência desta chamada — ver a taxonomia Technical/Semantic/Business em [EVENT_MODEL.md](EVENT_MODEL.md) |
| `memory` | array | Fragmentos de Memory relevantes anexados ou atualizados por esta chamada (ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md)) |
| `warnings` | array | Avisos não fatais — a chamada teve sucesso, mas algo merece atenção (ex: dado parcial, Digital Twin desatualizado — ver [DIGITAL_TWIN.md](DIGITAL_TWIN.md)) |
| `error` | object \| null | `{ code, message }` quando `success` é `false`. Nunca uma string solta |
| `audit` | object | Metadados de autorização desta chamada: `{ riskLevel, autonomyLevelRequired, autonomyLevelEffective }` — ver [§5](#5-autonomia-progressiva). O rastro de log em si não viaja no envelope; é escrito diretamente no Audit Engine, correlacionável por `correlationId`/`requestId` |
| `nextActions` | array | Ações sugeridas ou pendentes de decisão humana |

### Evolução desde a primeira proposta

`data` e `error` foram a primeira adição (ver [ADR-0026](docs/adr/0026-envelope-de-resposta-padronizado.md)). Nesta revisão, o envelope ganhou `protocolVersion`, `correlationId`, `requestId`, `timestamp`, `actor`, `intent`, `capability`, `warnings` e `audit` — e o antigo campo `mission`/`workspace` foi renomeado para `missionId`/`workspaceId` (deixando explícito que carregam um identificador, não o objeto inteiro) e o campo `logs` foi removido, substituído pela dupla `correlationId` + `audit`: em vez de ecoar entradas de log a cada resposta, qualquer consumidor consulta o histórico completo no Audit Engine pelo `correlationId`. Ver [ADR-0030](docs/adr/0030-envelope-v2.md).

### Quem produz o envelope

| Origem | Como chega ao envelope |
|---|---|
| Capability de uma Skill/Plugin | O Plugin produz sua resposta nativa; o Skill Engine normaliza para o envelope antes de devolver ao Agent Engine |
| Agent (Claude, ChatGPT, Gemini, Manus) | `services/ai-router` normaliza a resposta nativa de cada provedor de IA para o envelope antes de devolver ao Agent Engine |
| Canal externo (Telegram, GitHub, WhatsApp...) | O Plugin do canal (ver [/plugins](plugins/)) normaliza a resposta nativa da API externa para o envelope |

Nenhum consumidor interno do SIGMA (Mission Engine, Execution Engine, uma interface) trata um formato que não seja este envelope — se um formato nativo vaza sem tradução, é um defeito de implementação da Skill/Plugin/Agent responsável, não uma variação aceitável.

---

## 2. Intenção, não Comando

O SIGMA nunca interpreta uma frase como uma lista de comandos. Ele interpreta um objetivo — uma **Intent**, cujo propósito em linguagem natural fica no campo `objective` — que pode se decompor em múltiplas Missions relacionadas. Ver [ADR-0028](docs/adr/0028-intencao-nao-comando.md).

> **Nota de terminologia — "Objetivo" e Intent.** Em revisão, o termo "Objetivo" foi proposto como conceito central, com o exemplo "Sigma, quero fechar a venda da Sea Master" gerando Missions/Subtasks/Eventos. Lido junto com a especificação de [SGL](#3-sigma-language-sgl), onde `objective` já aparece como **campo dentro de um bloco `INTENT`**, a leitura adotada aqui é: **"Objetivo" é o nome de produto, em português, para o campo `objective` de uma Intent — não uma camada nova acima dela.** Isso evita renomear Intent Engine/`IntentDetected`/Release 6, já consolidados em seis ADRs e no roadmap. Se a intenção do Product Owner era uma camada `Objective` distinta e acima de `Intent` (ex: um Objective gerando múltiplas Intents), esta leitura precisa ser revista — sinalizado explicitamente em [ADR-0036](docs/adr/0036-objetivo-e-campo-da-intent.md), não assumido silenciosamente.

### Declarativo, não imperativo

Uma Intent descreve um **estado desejado**, não uma sequência de passos. Não se diz *"atualize o orçamento"* — diz-se *"o orçamento da Sea Master precisa refletir as decisões tomadas nesta reunião"*. O SIGMA decide o **como**. Isso desacopla completamente a intenção da implementação: se o Gestor.Alfa mudar de API, se uma nova IA surgir, ou se uma Skill for substituída, a Intent registrada continua válida e correta sem alteração. Ver [ADR-0037](docs/adr/0037-declarativo-nao-imperativo.md).

### Exemplo canônico

> "Sigma, quero fechar a venda da Sea Master."

Isto não é um comando. É uma Intent com objective: **"Fechar a venda da Sea Master."** — um estado desejado, não uma lista de passos.

```
Intent { objective: "Fechar a venda da Sea Master" }
│
├── Mission 1 — Registrar participação e ata da reunião comercial
├── Mission 2 — Atualizar Gestor.Alfa (CRM) com o resultado
├── Mission 3 — Ajustar orçamento — autonomia mínima: Assistido (confirmação humana)
├── Mission 4 — Enviar proposta
├── Mission 5 — Agendar follow-up
└── Mission 6 — Lembrete em 3 dias (Mission diferida — services/scheduler)
```

Todas as Missions carregam o mesmo `correlationId` de origem — o Audit Engine responde tanto "o que a Mission 3 fez" quanto "tudo que resultou desta Intent", e o Planner pode decidir não criar a Mission 3 se, ao investigar, o orçamento já estiver correto. Isso é o que torna o SIGMA orientado a objetivos (declarativo) em vez de reativo a uma lista literal de instruções (imperativo).

---

## 3. SIGMA Language (SGL)

Uma linguagem intermediária, estruturada e legível, para representar Intents e Missions internamente — o Envelope (JSON) continua sendo o formato de transporte entre Engines; SGL é a forma em que uma Intent é *pensada* antes de virar JSON, mais próxima de como um modelo de linguagem ou uma pessoa descreveria um objetivo. Especificação completa, gramática e exemplos em **[SGL.md](SGL.md)**. Ver [ADR-0032](docs/adr/0032-sigma-language.md).

```
INTENT
type: meeting-followup
client: Sea Master
objective: Fechar a venda da Sea Master
participants:
  Victor
  Felipe
expected:
  Budget Updated
  CRM Updated
  Follow-up Scheduled
```

O Intent Engine (Release 6) produz SGL a partir de linguagem natural; o Planner Engine (Release 5) consome SGL (ou a Intent estruturada equivalente em JSON — ambas as formas são intercambiáveis, SGL → JSON é uma transformação sem perda) para decidir o Plan.

---

## 4. Capability e Capability Registry

Uma Skill não expõe funções soltas — expõe um conjunto nomeado de **Capabilities**, cada uma catalogada no **Capability Registry**, mantido pelo Skill Engine a partir dos manifests de todos os Plugins carregados. Ver [ADR-0027](docs/adr/0027-capability-unidade-de-skill.md) e [ADR-0033](docs/adr/0033-capability-registry.md).

```
Skill: GoogleCalendarSkill (Plugin: calendar)
└── Capabilities:
      ├── CreateEvent   (v1.0.0 · owner: Skill Engine team · autonomy_level_required: 2)
      ├── CancelEvent   (v1.0.0 · autonomy_level_required: 1)
      ├── MoveEvent     (v1.0.0 · autonomy_level_required: 1)
      └── SearchAgenda  (v1.0.0 · autonomy_level_required: 3)
```

### Definição formal de uma Capability (Registry entry)

```json
{
  "name": "CreateEvent",
  "version": "1.0.0",
  "owner": "calendar-plugin",
  "skill": "GoogleCalendarSkill",
  "plugin": "calendar",
  "input": "CreateEventInput",
  "output": "Meeting",
  "autonomy_level_required": 2,
  "events_emitted": ["meeting.scheduled"],
  "dependencies": []
}
```

`version`, `owner` e `dependencies` foram acrescentados ao contrato de Capability nesta revisão — permitem versionar uma Capability independentemente da versão do Plugin que a implementa, atribuir responsabilidade, e declarar quando uma Capability só faz sentido se outra já existir (ex: `MoveEvent` pode depender de `CreateEvent` já ter sido implementada). A saída de toda Capability chega ao chamador dentro do campo `data` do [Envelope](#1-o-envelope).

---

## 5. Autonomia Progressiva

Quatro níveis, configuráveis por User/Role e exigidos por Capability. Ver [ADR-0029](docs/adr/0029-autonomia-progressiva.md).

| Nível | Nome | Comportamento |
|---|---|---|
| 0 | Consultivo | Apenas sugere — nunca executa |
| 1 | Assistido | Executa somente após confirmação explícita, chamada a chamada |
| 2 | Delegado | Executa sem confirmação para Capabilities previamente autorizadas |
| 3 | Operacional | Orquestra Missions completas (de uma mesma Intent) sem confirmação por etapa, dentro de limites definidos |

**Regra de resolução**: `audit.autonomyLevelEffective` (ver [Envelope](#1-o-envelope)) = **menor** valor entre o nível do User/Role e o `autonomy_level_required` da Capability. Uma Capability nunca executa acima do que o usuário tem permissão, e um usuário Operacional (3) ainda respeita uma Capability marcada como sempre-Assistida (1) — ex: uma ação financeira irreversível.

Quando o nível efetivo exige confirmação, a Capability não é executada; a ação pendente é descrita em `nextActions` do Envelope, aguardando decisão humana antes de prosseguir.

---

## 6. Digital Twin

Nenhum Engine ou Capability lê um sistema externo diretamente a cada chamada — lê a representação viva e sincronizada desse objeto: seu **Digital Twin**. Client, Project, Company e User possuem Digital Twin. Especificação completa em **[DIGITAL_TWIN.md](DIGITAL_TWIN.md)**. Ver [ADR-0035](docs/adr/0035-digital-twin.md).

```
Client (Sea Master) → ClientTwin (SIGMA) → Gestor.Alfa (fonte da verdade)
```

Leituras (Planner resolvendo contexto, Agent Engine montando o contexto de uma Subtask, Workspace agregando entidades) consultam o Twin. Escritas (uma Capability como `CreateBudget`) sempre passam pelo Skill Engine → Plugin → API externa, como já definido em [ADR-0007](docs/adr/0007-comunicacao-somente-via-api.md) — o Twin nunca é a fonte da verdade, é atualizado a partir do Semantic Event que a escrita gera.

---

## 7. Eventos: Technical, Semantic, Business

Nem todo evento serve ao mesmo consumidor. O catálogo completo e a taxonomia de três camadas vivem em **[EVENT_MODEL.md](EVENT_MODEL.md)**. Ver [ADR-0034](docs/adr/0034-eventos-tres-camadas.md).

| Camada | Serve a | Exemplo |
|---|---|---|
| Technical | Orquestração interna entre Engines | `IntentDetected`, `SkillRequested` |
| Semantic | O resultado de uma Capability específica | `meeting.scheduled`, `budget.created_via_gestor` |
| Business | Analytics e visão de negócio, curados a partir de eventos Semantic | `BudgetApproved`, `ClientCreated` |

O campo `events` do [Envelope](#1-o-envelope) carrega eventos de qualquer uma das três camadas; o consumidor filtra pela camada que lhe interessa.

---

## 8. Ordem de Runtime vs. Ordem de Desenvolvimento

Uma Mission, em produção, sempre percorre: **Intent → Planner → Mission → Execution**. Isso não obriga o SIGMA a ser *construído* nessa ordem — o roadmap de Releases segue: **Protocol → Kernel/Bootstrap → Memory → Mission → Planner → Intent → Skill → Agent → ...** (ver [ROADMAP.md](ROADMAP.md)). Cada Release constrói contra um contrato já definido pelo Protocol, usando entradas mockadas/manuais para o que ainda não existe (ex: o Planner é construído e testado contra Intents estruturadas manualmente, antes de o Intent Engine existir para produzi-las de verdade). Ver [ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md), que fecha a tensão sinalizada em [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md).

---

## 9. Como cada par de peças conversa

Síntese de contrato por relação — o catálogo completo de eventos vive em [EVENT_MODEL.md](EVENT_MODEL.md); esta tabela mapeia cada relação ao(s) evento(s) e ao uso do Envelope.

| Relação | Como conversam |
|---|---|
| Usuário/Sistema → Intent Engine | Linguagem natural ou evento estruturado → Intent Engine responde no Envelope, com `data` contendo a `Intent` estruturada (e, internamente, em SGL) |
| Intent Engine → Planner Engine | Evento `IntentDetected`, payload = Intent estruturada |
| Planner Engine → Memory Engine | Consulta síncrona (Knowledge/Memory relevante ao decidir o Plan) → resposta no Envelope, `data` contendo os fragmentos de Memory/Knowledge |
| Planner Engine → Mission Engine | Evento `MissionPlanned` por Mission decidida, payload = Plan (Subtasks, Agent/Skill candidatos) |
| Mission Engine → Agent Engine | Evento `SubtaskAssigned`, payload = Subtask + contexto de Workspace/Digital Twin resolvido |
| Agent Engine → Skill Engine | Evento `SkillRequested`, payload = Capability + input, nível de autonomia efetivo já resolvido |
| Skill Engine → Plugin | Chamada à Capability declarada no manifest (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) — resposta nativa do Plugin |
| Plugin → Skill Engine | Resposta nativa normalizada no [Envelope](#1-o-envelope) antes de subir |
| Skill Engine → Agent Engine | Envelope completo, incluindo `data`, `events`, `nextActions` |
| Agent Engine → Mission Engine | Envelope completo — Mission Engine decide o próximo estado da Subtask a partir de `success`/`error`/`nextActions` |
| Mission Engine → Execution Engine | Evento `ExecutionStarted`/`ExecutionValidated`/`ExecutionFailed` |
| Qualquer Engine → Audit Engine | Todo Envelope produzido é registrado, correlacionável por `correlationId`/`requestId` — nenhuma chamada passa sem rastro |
| Qualquer Engine → Kernel (contexto) | Leitura do contexto de execução ativo (Tenant/Workspace/User — ver [KERNEL.md](KERNEL.md) e [BOOTSTRAP.md](BOOTSTRAP.md)) — nunca escrita; contexto é resolvido uma vez por request |

---

## 10. Versionamento do protocolo

Este documento é versionado como um contrato público entre Engines — o campo `protocolVersion` do Envelope identifica qual versão produziu cada resposta. Uma mudança que altera o significado de um campo já existente do Envelope, renomeia uma Capability já catalogada, ou muda a regra de resolução de autonomia é uma mudança incompatível — exige um novo documento (`SIGMA_PROTOCOL.v2.md`) e um período de suporte a ambas as versões, nunca uma alteração silenciosa no arquivo atual enquanto Engines dependerem dele em produção. Antes de a Release 2 existir de fato, o protocolo pode evoluir livremente por revisão direta — não há consumidor em produção ainda para quebrar.

## 11. Relação com os demais documentos

Este documento não duplica conteúdo de [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md), [EVENT_MODEL.md](EVENT_MODEL.md), [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md), [SGL.md](SGL.md) ou [DIGITAL_TWIN.md](DIGITAL_TWIN.md) — é a camada de contrato que os une. Em caso de conflito aparente entre este documento e qualquer outro sobre formato de mensagem ou contrato entre Engines, este documento prevalece; o outro documento está desatualizado e deve ser corrigido.
