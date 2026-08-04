# SGL — SIGMA Language

Uma linguagem intermediária, estruturada e legível por humanos e por modelos de linguagem, para representar Intents e Missions **antes** de virarem JSON. O [Envelope](SIGMA_PROTOCOL.md#1-o-envelope) continua sendo o formato de transporte entre Engines — SGL é a forma em que uma Intent é *pensada*, mais próxima de como uma pessoa ou uma IA descreveria um objetivo. Ver [ADR-0032](docs/adr/0032-sigma-language.md).

## Por que uma linguagem própria

Um provedor de IA devolve texto, não sempre JSON bem-formado. Pedir a um modelo que raciocine diretamente em JSON aninhado é mais frágil (mais fácil de gerar um JSON malformado) do que pedir que ele produza um formato de blocos e pares chave-valor, próximo de YAML, que qualquer modelo lida bem e que converte deterministicamente para o Envelope. SGL existe para ser essa camada intermediária — reduz o acoplamento entre "qual IA está pensando" e "qual formato exato o Planner precisa consumir".

## Gramática

```
<BLOCO>
campo: valor
campo:
  item
  item
```

- Um documento SGL começa com uma palavra-chave de bloco em maiúsculas (`INTENT`, `MISSION`, `SUBTASK`).
- Campos são `chave: valor`, uma por linha.
- Um campo sem valor na mesma linha, seguido de linhas indentadas, é uma lista.
- Não há aninhamento além de bloco → campo → lista — SGL é deliberadamente raso; estruturas mais complexas pertencem ao JSON do Envelope, não a SGL.

## Exemplo — Intent

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

## Exemplo — Mission (gerada pelo Planner a partir da Intent acima)

```
MISSION
objective: Ajustar orçamento
client: Sea Master
capability: gestor.UpdateBudget
autonomy: assisted
dependsOn: meeting-followup
```

## Mapeamento SGL → Envelope

| SGL | Envelope |
|---|---|
| Bloco `INTENT` | `intent: { id, objective }` |
| Campo `objective` | `intent.objective` |
| Bloco `MISSION` | `missionId` + `data` da Mission criada |
| Campo `capability` | `capability.name` (`skill.Capability`) |
| Campo `autonomy` | usado para calcular `audit.autonomyLevelRequired` |

A conversão SGL → JSON é determinística e sem perda — todo campo de um bloco SGL tem um destino fixo no Envelope. Não existe informação que só exista em SGL e não possa ser representada no Envelope, nem o contrário.

## Quem fala SGL

- O **Intent Engine** (Release 6) produz SGL a partir de linguagem natural — é aqui que a ambiguidade de uma frase humana vira uma estrutura previsível.
- O **Planner Engine** (Release 5) consome SGL (ou o JSON equivalente já convertido) para decidir Missions e Subtasks.
- Agents (Claude, ChatGPT, Gemini, Manus), ao raciocinar sobre uma Subtask, podem produzir e consumir SGL internamente antes de sua resposta ser normalizada no Envelope pelo `services/ai-router`.

## Escopo desta especificação

Este documento define a gramática e o mapeamento — não um parser. Implementação (parser/serializer SGL↔JSON) nasce com a Release 5 — Planner Engine ou a Release 6 — Intent Engine, o que for construído primeiro (ver [ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md)). Até lá, exemplos SGL na documentação são ilustrativos.
