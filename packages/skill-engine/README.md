# packages/skill-engine

Implementação do Skill Engine: carrega e autoriza Plugins (ver [PLUGIN_SYSTEM.md](../../PLUGIN_SYSTEM.md)), expõe a cada Agent as Skills que ele está autorizado a invocar. Nunca conhece a implementação concreta de um Plugin — apenas seu `manifest.json` e o contrato padrão. Ver [ADR-0006](../../docs/adr/0006-integracao-externa-e-sempre-uma-skill.md) e [ADR-0017](../../docs/adr/0017-plugin-system.md).

Vazio na Fase Foundation. Camada L7 do [ROADMAP.md](../../ROADMAP.md). Primeiro Plugin candidato: [plugins/gestor](../../plugins/gestor/).
