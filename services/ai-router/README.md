# services/ai-router

Camada técnica de comunicação com cada provedor de IA (Claude, ChatGPT, Gemini, Manus): autenticação de API, rate limiting, retry, medição de custo e latência. Consumida exclusivamente por `packages/agent-engine` — nenhum outro pacote ou serviço fala com um provedor de IA diretamente, o que mantém a substituição de provedor (ver [ADR-0004](../../docs/adr/0004-tres-camadas-ia-agente-skill.md)) confinada a um único lugar do sistema.

Vazio na Fase Foundation. Nasce junto com a Release 8 — Agent Engine do [ROADMAP.md](../../ROADMAP.md).
