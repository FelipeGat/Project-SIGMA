# SIGMA Protocol

**O documento de maior autoridade técnica do projeto.** [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) descreve a topologia do SIGMA — quem fala com quem. Este documento descreve a língua — o que é dito, em que formato, sob quais regras. Nenhum Engine, Plugin ou Agent implementado a partir da Release 2 pode se desviar do que está definido aqui sem que isso seja, primeiro, uma mudança neste documento. Ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md).

## Por que este documento vem antes do Kernel

Já sabemos que Client, Project, Company, Skill, Agent, Mission, Memory e Workspace existem como conceitos (ver [DOMAIN.md](DOMAIN.md)). O que não estava definido era **como eles conversam**: o formato de uma resposta, como uma Mission muda de estado, como um Agent recebe contexto, como uma integração registra auditoria. Construir Engines sem esse contrato definido primeiro arrisca cada um inventar seu próprio formato — o retrabalho de integração entre nove Engines desalinhados é maior do que o custo de definir o protocolo uma vez, cedo. Ver [ADR-0025](docs/adr/0025-protocol-antecede-kernel.md).

---

## 1. O Envelope

Toda resposta produzida por uma Capability, por um Agent, ou por qualquer canal externo (Telegram, GitHub, Claude, ChatGPT, Gemini, Manus — sem exceção) é normalizada neste formato antes de retornar ao chamador:

```json
{
  "success": true,
  "data": null,
  "error": null,
  "mission": "mission-id",
  "workspace": "workspace-id",
  "events": [],
  "memory": [],
  "nextActions": [],
  "logs": []
}
```

| Campo | Tipo | Significado |
|---|---|---|
| `success` | boolean | Se a chamada foi concluída sem falha |
| `data` | object \| null | **Adicionado nesta especificação** — o resultado de negócio da chamada (ex: o `Meeting` criado por `CreateEvent`). Sem este campo, o envelope não teria onde carregar o payload que motivou a chamada. |
| `error` | object \| null | **Adicionado nesta especificação** — `{ "code": string, "message": string }` quando `success` é `false`. Nunca uma string solta; sempre estruturado, para permitir tratamento programático. |
| `mission` | string \| null | Identificador da Mission de origem, quando a chamada acontece no contexto de uma |
| `workspace` | string \| null | Identificador do Workspace de origem, quando aplicável (ver [WORKSPACES.md](WORKSPACES.md)) |
| `events` | array | Eventos de domínio publicados como consequência desta chamada (nomes do catálogo de [EVENT_MODEL.md](EVENT_MODEL.md)) |
| `memory` | array | Fragmentos de Memory relevantes anexados ou atualizados por esta chamada (ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md)) |
| `nextActions` | array | Ações sugeridas ou pendentes de decisão humana — substitui o antigo campo booleano `requires_human_approval` (ver [ADR-0026](docs/adr/0026-envelope-de-resposta-padronizado.md)) |
| `logs` | array | Entradas de log geradas por esta chamada, correlacionáveis pelo Audit Engine |

`data` e `error` foram acrescentados à proposta original do Product Owner — sinalizado explicitamente em [ADR-0026](docs/adr/0026-envelope-de-resposta-padronizado.md), não uma correção silenciosa.

### Quem produz o envelope

| Origem | Como chega ao envelope |
|---|---|
| Capability de uma Skill/Plugin | O Plugin produz sua resposta nativa; o Skill Engine normaliza para o envelope antes de devolver ao Agent Engine |
| Agent (Claude, ChatGPT, Gemini, Manus) | `services/ai-router` normaliza a resposta nativa de cada provedor de IA para o envelope antes de devolver ao Agent Engine |
| Canal externo (Telegram, GitHub, WhatsApp...) | O Plugin do canal (ver [/plugins](plugins/)) normaliza a resposta nativa da API externa para o envelope |

Nenhum consumidor interno do SIGMA (Mission Engine, Execution Engine, uma interface) trata um formato que não seja este envelope — se um formato nativo vaza sem tradução, é um defeito de implementação da Skill/Plugin/Agent responsável, não uma variação aceitável.

---

## 2. Intenção, não Comando

O SIGMA nunca interpreta uma frase como uma lista de comandos. Ele interpreta um objetivo — uma **Intent** — que pode se decompor em múltiplas Missions relacionadas. Ver [ADR-0028](docs/adr/0028-intencao-nao-comando.md).

### Exemplo canônico

> "Sigma, participe da reunião da Sea Master, atualize o Gestor, ajuste o orçamento e avise o Victor."

Isto não são quatro comandos. É uma Intent: **"Conduzir o pós-reunião comercial da Sea Master."**

```
Intent: "Conduzir o pós-reunião comercial da Sea Master"
│
├── Mission 1 — Registrar participação e ata da reunião
│     └── Subtask: acompanhar/registrar reunião (Skill: calendar/meeting)
│
├── Mission 2 — Atualizar Gestor.Alfa com o resultado
│     └── Subtask: CreateNote / UpdateOpportunity (Skill: gestor)
│
├── Mission 3 — Ajustar orçamento
│     └── Subtask: UpdateBudget (Skill: gestor) — Nível de autonomia: Assistido (confirmação humana)
│
└── Mission 4 — Notificar Victor
      └── Subtask: SendMessage (Skill: telegram ou whatsapp)
```

Todas as quatro Missions carregam a mesma `intent_id` de origem — o Audit Engine responde tanto "o que a Mission 3 fez" quanto "tudo que resultou da Intent original", e o Planner pode decidir não criar a Mission 3 se, ao investigar, o orçamento já estiver correto. Isso é o que torna o SIGMA orientado a objetivos em vez de reativo a uma lista literal de instruções.

---

## 3. Capability

Uma Skill não expõe funções soltas — expõe um conjunto nomeado de **Capabilities**. Ver [ADR-0027](docs/adr/0027-capability-unidade-de-skill.md).

```
Skill: GoogleCalendarSkill (Plugin: calendar)
└── Capabilities:
      ├── CreateEvent   (autonomy_level_required: 2 — Delegado)
      ├── CancelEvent   (autonomy_level_required: 1 — Assistido)
      ├── MoveEvent     (autonomy_level_required: 1 — Assistido)
      └── SearchAgenda  (autonomy_level_required: 3 — Operacional)
```

### Definição formal de uma Capability

```json
{
  "name": "CreateEvent",
  "skill": "GoogleCalendarSkill",
  "input": "CriarEventoInput",
  "output": "Meeting",
  "autonomy_level_required": 2,
  "events_emitted": ["meeting.scheduled"]
}
```

A saída de toda Capability chega ao chamador dentro do campo `data` do [Envelope](#1-o-envelope). O manifest de Plugin (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) lista suas Capabilities no campo `capabilities` — anteriormente chamado `actions`, renomeado nesta Release.

---

## 4. Autonomia Progressiva

Quatro níveis, configuráveis por User/Role e exigidos por Capability. Ver [ADR-0029](docs/adr/0029-autonomia-progressiva.md).

| Nível | Nome | Comportamento |
|---|---|---|
| 0 | Consultivo | Apenas sugere — nunca executa |
| 1 | Assistido | Executa somente após confirmação explícita, chamada a chamada |
| 2 | Delegado | Executa sem confirmação para Capabilities previamente autorizadas |
| 3 | Operacional | Orquestra Missions completas (de uma mesma Intent) sem confirmação por etapa, dentro de limites definidos |

**Regra de resolução**: nível efetivo = **menor** valor entre o nível do User/Role e o `autonomy_level_required` da Capability. Uma Capability nunca executa acima do que o usuário tem permissão, e um usuário Operacional (3) ainda respeita uma Capability marcada como sempre-Assistida (1) — ex: uma ação financeira irreversível.

Quando o nível efetivo exige confirmação, a chamada retorna `success: false`-adjacente (a Capability não foi executada) com a ação pendente descrita em `nextActions` do [Envelope](#1-o-envelope), aguardando decisão humana antes de prosseguir.

---

## 5. Como cada par de peças conversa

Síntese de contrato por relação — o catálogo completo de eventos vive em [EVENT_MODEL.md](EVENT_MODEL.md); esta tabela mapeia cada relação ao(s) evento(s) e ao uso do Envelope.

| Relação | Como conversam |
|---|---|
| Usuário/Sistema → Intent Engine | Linguagem natural ou evento estruturado → Intent Engine responde no Envelope, com `data` contendo a `Intent` estruturada |
| Intent Engine → Planner Engine | Evento `IntentDetected`, payload = Intent estruturada |
| Planner Engine → Memory Engine | Consulta síncrona (Knowledge/Memory relevante ao decidir o Plan) → resposta no Envelope, `data` contendo os fragmentos de Memory/Knowledge |
| Planner Engine → Mission Engine | Evento `MissionPlanned` por Mission decidida, payload = Plan (Subtasks, Agent/Skill candidatos) |
| Mission Engine → Agent Engine | Evento `SubtaskAssigned`, payload = Subtask + contexto de Workspace resolvido |
| Agent Engine → Skill Engine | Evento `SkillRequested`, payload = Capability + input, credenciais de autorização (nível de autonomia efetivo já resolvido) |
| Skill Engine → Plugin | Chamada ao `action`/Capability declarado no manifest (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) — resposta nativa do Plugin |
| Plugin → Skill Engine | Resposta nativa normalizada no [Envelope](#1-o-envelope) antes de subir |
| Skill Engine → Agent Engine | Envelope completo, incluindo `data`, `events`, `nextActions` |
| Agent Engine → Mission Engine | Envelope completo — Mission Engine decide o próximo estado da Subtask a partir de `success`/`error`/`nextActions` |
| Mission Engine → Execution Engine | Evento `ExecutionStarted`/`ExecutionValidated`/`ExecutionFailed` |
| Qualquer Engine → Audit Engine | Todo Envelope produzido gera uma entrada em `logs`, consumida pelo Audit Engine — nenhuma chamada passa sem rastro |
| Qualquer Engine → Kernel (contexto) | Leitura do contexto de execução ativo (Tenant/Workspace/User — ver [KERNEL.md](KERNEL.md)) — nunca escrita; contexto é resolvido uma vez por request |

---

## 6. Versionamento do protocolo

Este documento é versionado como um contrato público entre Engines. Uma mudança que altera o significado de um campo já existente do Envelope, renomeia uma Capability já catalogada, ou muda a regra de resolução de autonomia é uma mudança incompatível — exige um novo documento (`SIGMA_PROTOCOL.v2.md`) e um período de suporte a ambas as versões, nunca uma alteração silenciosa no arquivo atual enquanto Engines dependerem dele em produção. Antes da Release 2 (Kernel) existir de fato, o protocolo pode evoluir livremente por revisão direta — não há consumidor em produção ainda para quebrar.

## 7. Relação com os demais documentos

Este documento não duplica conteúdo de [ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md), [EVENT_MODEL.md](EVENT_MODEL.md) ou [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md) — é a camada de contrato que os une. Em caso de conflito aparente entre este documento e qualquer outro sobre formato de mensagem ou contrato entre Engines, este documento prevalece; o outro documento está desatualizado e deve ser corrigido.
