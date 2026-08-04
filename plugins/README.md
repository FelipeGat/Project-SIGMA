# plugins

Empacotamento técnico de cada Skill, seguindo o [Plugin System](../PLUGIN_SYSTEM.md). O Kernel carrega Plugins através do Skill Engine — nunca conhece a implementação concreta de nenhum.

Cada Plugin implementa exatamente uma Skill já especificada em [/skills](../skills/). Nenhum código de ação (`actions/`) existe ainda — só o manifesto, que já é suficiente para revisar o contrato antes de qualquer implementação.

| Plugin | Skill correspondente | Manifest |
|---|---|---|
| gestor | [skills/gestor.md](../skills/gestor.md) | [gestor/manifest.json](gestor/manifest.json) |
| github | [skills/github.md](../skills/github.md) | [github/manifest.json](github/manifest.json) |
| telegram | [skills/telegram.md](../skills/telegram.md) | [telegram/manifest.json](telegram/manifest.json) |
| calendar | [skills/calendar.md](../skills/calendar.md) | [calendar/manifest.json](calendar/manifest.json) |
| email | [skills/email.md](../skills/email.md) | [email/manifest.json](email/manifest.json) |
| whatsapp | [skills/whatsapp.md](../skills/whatsapp.md) | [whatsapp/manifest.json](whatsapp/manifest.json) |

Schema formal: [manifest.schema.json](manifest.schema.json).
