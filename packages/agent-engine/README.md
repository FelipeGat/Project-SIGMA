# packages/agent-engine

Implementação do Agent Engine: entidades `IA` (provedor) e `Agent` (persona), contrato `AgentPort`, decide qual Agent executa cada Subtask delegada pelo Mission Engine. Ver [ADR-0004](../../docs/adr/0004-tres-camadas-ia-agente-skill.md) e a documentação de cada Agent em [/agents](../../agents/). Consome `services/ai-router` para a comunicação técnica com cada provedor de IA.

Vazio na Fase Foundation. Camada L6 do [ROADMAP.md](../../ROADMAP.md).
