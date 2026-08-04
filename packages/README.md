# packages

Cada Engine do núcleo do SIGMA (ver [ARCHITECTURE.md §2](../docs/architecture/ARCHITECTURE.md) e [ADR-0011](../docs/adr/0011-arquitetura-em-camadas-de-engines.md)), mais bibliotecas compartilhadas, vive aqui como um pacote independente — versionável, testável e substituível isoladamente. Serviços deployáveis ([/services](../services/)) e apps ([/apps](../apps/)) dependem destes pacotes; nenhum pacote depende de um serviço ou app.

Mecânica técnica (PHP/Composer): cada pacote é um pacote Composer próprio (`composer.json` local), referenciado pelos serviços que o consomem via `path repository` — sem publicação num registry externo enquanto o monorepo for a única forma de consumo. Isso só é definido quando o primeiro pacote for implementado; nenhum `composer.json` existe ainda na Fase Foundation.

| Pacote | Corresponde a | Depende de |
|---|---|---|
| [core/](core/) | Primitivas de domínio compartilhadas (Value Objects comuns, contratos base) | — |
| [kernel/](kernel/) | Kernel — ver [KERNEL.md](../KERNEL.md) | core |
| [identity-engine/](identity-engine/) | Identity Engine — ver [MULTITENANCY.md](../MULTITENANCY.md) | core, kernel |
| [intent-engine/](intent-engine/) | Intent Engine | core, kernel |
| [planner-engine/](planner-engine/) | Planner Engine | core, kernel, intent-engine |
| [mission-engine/](mission-engine/) | Mission Engine | core, kernel, planner-engine |
| [memory-engine/](memory-engine/) | Memory Engine — ver [MEMORY_ARCHITECTURE.md](../MEMORY_ARCHITECTURE.md) | core, kernel |
| [agent-engine/](agent-engine/) | Agent Engine | core, kernel, mission-engine |
| [skill-engine/](skill-engine/) | Skill Engine — carrega Plugins, ver [PLUGIN_SYSTEM.md](../PLUGIN_SYSTEM.md) | core, kernel |
| [execution-engine/](execution-engine/) | Execution Engine | core, kernel, mission-engine |
| [audit-engine/](audit-engine/) | Audit Engine — ver [TELEMETRY.md](../TELEMETRY.md) | core, kernel |
| [design-system/](design-system/) | Componentes/tokens visuais compartilhados por `apps/web` e `apps/mobile` | — |
| [sdk/](sdk/) | SDK público para sistemas externos integrarem com o SIGMA sem depender de detalhes internos (ver [VISION_2030.md](../VISION_2030.md)) | core |

`execution-engine` não constava na lista original da revisão que originou esta estrutura (Sprint 0.2) — incluído para manter consistência com os Engines já aprovados em [ADR-0011](../docs/adr/0011-arquitetura-em-camadas-de-engines.md). `identity-engine` foi acrescentado na Release 1 (revisão de CTO), extraído do que seria `memory-engine` — ver [ADR-0039](../docs/adr/0039-identity-engine.md). O núcleo tem hoje dez Engines, não nove.
