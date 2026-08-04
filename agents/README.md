# Agents

Documentação — ainda sem código — de cada Agent que o **Agent Engine** pode escalar para executar uma Subtask. Um Agent é uma persona operacional especializada que usa uma IA (ver [DOMAIN.md](../DOMAIN.md) e [ADR-0004](../docs/adr/0004-tres-camadas-ia-agente-skill.md)) — não confundir Agent com IA: o Agent é a especialidade e o contrato de operação; a IA é o provedor por trás dele, substituível.

Cada arquivo desta pasta segue a mesma estrutura:

- **Missão** — a especialidade deste Agent dentro do SIGMA.
- **Responsabilidades** — o que ele decide e produz.
- **Limites** — o que ele explicitamente não faz, mesmo que tecnicamente pudesse.
- **Entradas** — o que o Agent Engine entrega a ele ao delegar uma Subtask.
- **Saídas** — o formato do resultado que ele devolve ao Agent Engine.
- **Permissões** — quais Skills este Agent está autorizado a solicitar, e sob quais condições.

| Agent | IA | Especialidade |
|---|---|---|
| [claude.md](claude.md) | Claude | Engenharia de Software |
| [chatgpt.md](chatgpt.md) | ChatGPT | Estratégia |
| [gemini.md](gemini.md) | Gemini | Design |
| [manus.md](manus.md) | Manus | Documentação |

Nenhum Agent decide o Plan de uma Mission — isso é responsabilidade do Planner Engine. Um Agent executa a Subtask que lhe foi atribuída. Ver [ADR-0012](../docs/adr/0012-planner-decide-nunca-a-ia.md).
