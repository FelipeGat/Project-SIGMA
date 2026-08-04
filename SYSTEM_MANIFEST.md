# System Manifest & Self-Describing Components

Dois conceitos complementares: o **System Manifest** é o que se declara (o que deveria compor o sistema); **Self-Describing Components** é o que cada peça, uma vez carregada, sabe dizer sobre si mesma. O Bootstrap lê o primeiro; reconcilia contra o segundo. Ver [ADR-0045](docs/adr/0045-system-manifest.md) e [ADR-0046](docs/adr/0046-self-describing-components.md).

Não confundir com o `manifest.json` de um [Plugin](PLUGIN_SYSTEM.md) — aquele descreve uma única Skill. Este documento descreve o **sistema inteiro**.

## System Manifest

Um único arquivo, lido pelo Bootstrap no passo `discover` do [Lifecycle](BOOTSTRAP.md#como-o-sigma-inicia) — a única coisa que o Bootstrap precisa saber de antemão. Tudo o mais é descoberto a partir daqui.

```yaml
manifestVersion: 1

project: SIGMA
version: 1

engines:
  - identity
  - memory
  - mission
  - planner
  - intent
  - skill
  - agent

plugins:
  - telegram
  - gestor
  - github

providers:
  - claude
  - chatgpt
  - gemini
  - manus

workspace:
  - alfa
```

| Campo | Significa |
|---|---|
| `manifestVersion` | Versão do **formato** deste arquivo — não da instalação do SIGMA. O Bootstrap rejeita qualquer `manifestVersion` que não reconheça, em vez de tentar interpretar um formato desconhecido. Existe desde já (Release 2) porque o formato do Manifest vai crescer (`engines`, `plugins` completos chegam só com Releases futuras) — ver [ADR-0058](docs/adr/0058-manifest-version.md) |
| `project`/`version` | Identidade e versão da instalação do SIGMA |
| `engines` | Quais Engines (Modules de `kind: "engine"`) devem ser carregados |
| `plugins` | Quais Plugins (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) devem ser descobertos e registrados |
| `providers` | Quais provedores de IA (ver [DOMAIN.md](DOMAIN.md) — entidade `IA`) estão disponíveis a `services/ai-router` |
| `workspace` | Quais Tenants/Companies este ambiente atende |

O Bootstrap nunca infere o que carregar a partir do conteúdo de `packages/` ou `plugins/` no disco — só carrega o que o Manifest lista. Um Module presente no monorepo mas ausente do Manifest simplesmente não sobe; isso é intencional (permite, por exemplo, um ambiente de homologação rodar um subconjunto de Engines).

## Self-Describing Components

Todo Module — Engine, Plugin, Service ou Agent — responde, em runtime, a "quem é você":

```yaml
id: telegram
type: plugin
version: 1.0.0
status: ready
capabilities:
  - SendMessage
  - ReceiveWebhook
dependencies:
  - kernel
  - event-bus
health: healthy
```

| Campo | Significa |
|---|---|
| `id` | Identificador único do Module |
| `type` | `engine` \| `plugin` \| `service` \| `agent` \| `package` |
| `version` | Versão do Module (independente da versão do SIGMA) |
| `status` | Estado atual no Lifecycle — `boot`/`start`/`ready`/`degraded`/`shutdown` |
| `capabilities` | O que este Module oferece — para um Plugin, suas [Capabilities](SIGMA_PROTOCOL.md#4-capability-e-capability-registry); para um Engine, os contratos que expõe a outros Engines |
| `dependencies` | De quais outros Modules este depende |
| `health` | `healthy` \| `degraded` \| `unhealthy`, com detalhe quando aplicável |

## Por que isso importa

Um sistema onde todo componente se descreve sozinho permite que o próprio SIGMA:

- monte automaticamente um diagrama de arquitetura a partir do que está de fato carregado, não de um diagrama desenhado à mão e sujeito a ficar desatualizado;
- valide dependências **antes** de subir — se o Manifest lista um Plugin cuja dependência declarada não está presente, o Bootstrap recusa a subida com uma mensagem explícita, não uma falha tardia em runtime;
- detecte incompatibilidade de versão entre Modules antes que isso vire um bug em produção;
- responda perguntas como "quais Modules dependem do event-bus?" ou "quais Plugins oferecem a Capability `CreateBudget`?" via consulta simples sobre os descriptors já coletados — sem grep no código-fonte;
- sirva de base, no futuro, para uma interface de administração que não precisa de nenhuma tela escrita à mão para listar o que está rodando — ela lê os descriptors.

Essa característica aproxima o SIGMA de Kubernetes e do modelo de extensões do VS Code: o núcleo não sabe o que vai carregar, apenas sabe descobrir e perguntar.

## Escopo desta especificação

Este documento define o **formato** do System Manifest e do descriptor de Self-Describing Components. A implementação (parser do Manifest, coleta e agregação de descriptors, validação de dependência antes do boot) nasce com a Release 2 — SIGMA Bootstrap. O uso avançado (diagrama automático, busca por Capability, painel administrativo) é mencionado aqui como motivação, não como escopo desta Release — ver [docs/releases/0002-sigma-bootstrap.md](docs/releases/0002-sigma-bootstrap.md) para o que de fato entra agora.
