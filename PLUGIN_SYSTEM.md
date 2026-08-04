# Plugin System

## Skill é o conceito de domínio. Plugin é como ele é empacotado

[DOMAIN.md](DOMAIN.md) define `Skill` como uma capacidade de ação concreta que um Agent pode invocar. Este documento define **como** uma Skill é implementada tecnicamente: como um **Plugin**, carregado dinamicamente pelo Skill Engine — nunca como uma classe compilada e referenciada diretamente no código do Kernel ou de qualquer Engine.

## Por que não Skills compiladas

Se o Kernel (ou qualquer Engine) importa e referencia a classe concreta de uma integração (`use App\Skills\GestorSkill`), adicionar, remover ou atualizar uma integração exige alterar e reimplantar o núcleo do sistema. Isso viola diretamente o [MANIFESTO.md](MANIFESTO.md) — "toda integração pode ser substituída" deixa de ser verdade na prática assim que o núcleo precisa saber, em tempo de compilação, quais integrações existem.

## Como funciona

1. Um Plugin é um diretório em [/plugins](plugins/), identificado por um `manifest.json` que descreve — sem o Kernel precisar importar nenhum código do Plugin — tudo que o Skill Engine precisa saber para carregá-lo, autorizá-lo e invocá-lo.
2. O Skill Engine descobre Plugins em tempo de execução, lê o manifest, e os registra como Skills disponíveis para os Agents autorizados.
3. O Kernel **nunca conhece a implementação concreta** de um Plugin — apenas seu manifest e o contrato padrão (Configuração, Permissões, Entrada, Saída, Eventos, Logs, Testes, Documentação — já descrito em [ARCHITECTURE.md §7](docs/architecture/ARCHITECTURE.md)).

## Estrutura de um Plugin

```
plugins/<nome>/
├── manifest.json      # identidade, versão, skill correspondente, config, permissões, eventos, capabilities
├── README.md           # documentação legível — o que faz, como configurar
├── config/              # valores de configuração por ambiente (sem segredo em texto puro)
├── permissions/         # regras de autorização por Agent/Mission/Capability
├── events/               # eventos que este Plugin publica
└── capabilities/          # implementação de cada Capability que o Skill Engine invoca (nasce com o épico)
```

## `manifest.json` — schema de referência

Ver [plugins/manifest.schema.json](plugins/manifest.schema.json) para o schema formal. Campos principais:

| Campo | Descrição |
|---|---|
| `name` | Identificador único do Plugin (ex: `gestor`) |
| `version` | Versão semântica do Plugin |
| `skill` | Nome da Skill de domínio que este Plugin implementa (ver [/skills](skills/)) |
| `config` | Schema dos parâmetros de configuração aceitos |
| `permissions` | Operações que o Plugin expõe e o nível de risco de cada uma |
| `events` | Eventos que o Plugin publica no Event Bus |
| `capabilities` | As Capabilities (ver [SIGMA_PROTOCOL.md §3](SIGMA_PROTOCOL.md#3-capability)) que o Plugin expõe ao Skill Engine — cada uma com seu `autonomy_level_required` (ver [SIGMA_PROTOCOL.md §4](SIGMA_PROTOCOL.md#4-autonomia-progressiva)). Campo renomeado de `actions` na Release 1 ([ADR-0027](docs/adr/0027-capability-unidade-de-skill.md)) |

Toda resposta de uma Capability é normalizada no [Envelope do SIGMA Protocol](SIGMA_PROTOCOL.md#1-o-envelope) pelo Skill Engine antes de subir ao Agent Engine — nunca o formato nativo do Plugin repassado sem tradução.

## Relação com `/skills`

`/skills/*.md` documenta o *quê* e o *porquê* de cada Skill (contrato de domínio, casos de uso). `/plugins/*/manifest.json` documenta o *como* — o empacotamento técnico que o Skill Engine efetivamente carrega. Todo Plugin implementa exatamente uma Skill já especificada em `/skills`; não existe Plugin sem Skill correspondente documentada.

## Consequências para o Kernel

Ver [KERNEL.md](KERNEL.md): carregar Plugins pertence ao Kernel (via Skill Engine); conhecer a implementação de um Plugin específico nunca pertence.
