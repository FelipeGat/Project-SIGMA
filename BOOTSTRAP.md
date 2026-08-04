# Bootstrap

Como o SIGMA inicia — o design de referência da Release 2 (SIGMA Bootstrap), escrito antes de qualquer código dela. Escopo estritamente de infraestrutura: Configuration Provider, Telemetry, DI Container, Modules, System Manifest, Lifecycle, Health. **Não cobre** Mission, Identity, IA/Agent, ou carregamento de Plugin — isso chega em Releases posteriores, sobre esta fundação. Ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md).

## O Bootstrap nunca conhece Engines

Esta é a decisão mais importante deste documento. O Bootstrap não sabe que "Mission Engine", "Planner Engine" ou "Agent Engine" existem — ele conhece apenas **Module**, uma abstração genérica. Um Engine, um Plugin, um Service, um Package são todos, do ponto de vista do Bootstrap, apenas Modules que se registram, declaram dependências e têm um ciclo de vida. Ver [ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md).

Isso mantém o Kernel completamente genérico e reutilizável — nenhuma mudança no domínio (uma nova Engine, um novo tipo de Plugin) jamais exige tocar no Bootstrap.

## Como o SIGMA inicia

```
discover → register → boot → start → ready → (degraded)* → shutdown
```

1. **discover** — o Bootstrap lê o [System Manifest](SYSTEM_MANIFEST.md) (um único arquivo declarando o que deveria existir: quais Engines, Plugins, Providers, Workspace) e localiza os Modules correspondentes — nada além do que o Manifest declara é carregado.
2. **register** — cada Module descoberto declara seus bindings no DI Container e sua configuração (ver Configuration Provider, abaixo) — nenhum `boot()` roda ainda.
3. **boot** — Configuration Provider e Telemetry inicializam primeiro (falha aqui é sempre alta e explícita — nunca um valor `null` se propagando); em seguida cada Module roda seu `boot()`, na ordem topológica resolvida por `dependsOn`.
4. **start** — Modules abrem as conexões que precisam (na Release 2, apenas Redis para o Event Bus — sem banco de dados, ver [ADR-0053](docs/adr/0053-escopo-restrito-release-2.md)) e ficam prontos para operar.
5. **ready** — todos os Modules obrigatórios reportaram sucesso; `/health/ready` responde `200`.
6. **degraded** *(não é uma etapa linear — um estado que qualquer Module pode assumir depois de `ready`)* — um Module específico para de funcionar (ex: a integração com o Telegram cai) sem derrubar o sistema inteiro; apenas aquele Module é marcado `degraded`, refletido em `/health/ready` de forma granular por Module, nunca como um "tudo ou nada".
7. **shutdown** — ao receber sinal de encerramento, Modules encerram na ordem inversa de dependência, drenando trabalho em andamento antes de fechar conexões.

Ver [ADR-0041](docs/adr/0041-lifecycle-estendido.md).

## Module — a única unidade que o Bootstrap conhece

```
Module {
  name: string
  kind: "engine" | "plugin" | "service" | "package"
  dependsOn: string[]
  config(): ConfigSchema           // o que este Module precisa de configuração — ver Configuration Provider
  register(container: IContainer): void   // registra bindings no DI Container
  boot(): Promise<void>              // inicialização, após todos os bindings existirem
  describe(): ComponentDescriptor      // ver SYSTEM_MANIFEST.md — Self-Describing Components
}
```

## Kernel API — apenas interfaces

Um Module nunca recebe ou importa uma classe concreta do Kernel — apenas interfaces, injetadas via `register(container)`. O Kernel expõe exatamente seis: `ILogger`, `IEventBus`, `IModule`, `IConfiguration`, `IHealth`, `IContainer`. A implementação concreta por trás de cada uma pode mudar sem que nenhum Module precise mudar. Ver [ADR-0052](docs/adr/0052-kernel-api-apenas-interfaces.md).

| Interface | Responsabilidade |
|---|---|
| `IContainer` | Resolver dependências por contrato |
| `IConfiguration` | Ler a configuração já resolvida deste Module (ver Configuration Provider) |
| `ILogger` | Emitir Logs estruturados, correlacionados |
| `IEventBus` | Publicar/assinar eventos (mecanismo — ver [EVENT_MODEL.md](EVENT_MODEL.md)) |
| `IHealth` | Reportar o próprio estado (`ready`/`degraded`) ao Health Manager |
| `IModule` | O contrato acima — implementado por todo Engine/Plugin/Service/Package |

`kind` é metadado, não comportamento — o Bootstrap trata todo Module da mesma forma, independente do valor de `kind`. Um Engine (Release 4 em diante) é, tecnicamente, apenas um Module com `kind: "engine"`; o mesmo vale para um Plugin (Release 8) ou um Service (`services/*`).

## Configuration Provider

Não uma função `config()` global lida de qualquer lugar — cada Module registra, via `config()`, o schema da configuração que precisa (variáveis obrigatórias, opcionais, valores default). O Configuration Provider central resolve essas declarações contra o ambiente (variáveis de ambiente, arquivo por ambiente) e falha no `boot` — não depois — se algo obrigatório faltar. Nenhum Module lê variável de ambiente diretamente. Ver [ADR-0044](docs/adr/0044-configuration-provider.md).

## Telemetry, não apenas Logger

O Bootstrap inicializa Telemetry completa desde o primeiro instante — os quatro pilares já definidos em [TELEMETRY.md](TELEMETRY.md): Logs, Metrics, Tracing, Audit — não um logger isolado. A partir do primeiro `discover`, toda etapa do boot já é observável. Ver [ADR-0043](docs/adr/0043-telemetry-desde-o-bootstrap.md).

## Health — compatível com Kubernetes

Três endpoints, não um:

| Endpoint | Responde |
|---|---|
| `GET /health/live` | O processo está vivo (não travado) — usado para decidir se o orquestrador deve reiniciar o processo |
| `GET /health/ready` | Todos os Modules obrigatórios estão `ready` (não `degraded` nem `boot`) — usado para decidir se o processo deve receber tráfego |
| `GET /health/startup` | O boot inicial já terminou (mesmo que ainda não esteja `ready`) — usado para dar mais tempo a processos que demoram para iniciar, sem que `/health/live` os mate prematuramente |

Cada resposta é um [Envelope do SIGMA Protocol](SIGMA_PROTOCOL.md#1-o-envelope), com `data` detalhando o estado por Module quando relevante (permitindo granularidade `degraded` por Module, não apenas um booleano geral). Ver [ADR-0042](docs/adr/0042-health-estilo-kubernetes.md).

## System Manifest e Self-Describing Components

O Bootstrap lê **um único arquivo** na subida — o [System Manifest](SYSTEM_MANIFEST.md) — declarando o que deveria compor o sistema. Tudo além disso é descoberto: cada Module, ao ser carregado, é capaz de descrever a si mesmo (`describe()`) — quem é, o que oferece, do que depende, em que estado está. Ver [SYSTEM_MANIFEST.md](SYSTEM_MANIFEST.md), [ADR-0045](docs/adr/0045-system-manifest.md) e [ADR-0046](docs/adr/0046-self-describing-components.md).

## Como Plugins são descobertos

**Fora do escopo da Release 2.** O mecanismo de `Modules` descrito aqui é genérico o suficiente para, na Release 8 (Skill Engine), Plugins serem descobertos e registrados da mesma forma — um Plugin válido se torna, na prática, um Module com `kind: "plugin"`, cujo manifest (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) descreve seus bindings. A Release 2 não implementa esse carregamento; apenas não fecha portas para ele.

## Relação com KERNEL.md

[KERNEL.md](KERNEL.md) descreve o escopo conceitual completo do Kernel. Este documento é o design da Release 2, o primeiro incremento desse escopo — e confirma, especificamente, que o Kernel nunca conhece Engine, Mission, ou qualquer conceito de domínio: apenas Module. Ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md) e [ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md).
