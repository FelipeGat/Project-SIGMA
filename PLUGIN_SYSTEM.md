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
├── manifest.json      # identidade, versão, skill correspondente, config, permissões, eventos, ações
├── README.md           # documentação legível — o que faz, como configurar
├── config/              # valores de configuração por ambiente (sem segredo em texto puro)
├── permissions/         # regras de autorização por Agent/Mission
├── events/               # eventos que este Plugin publica
└── actions/               # pontos de entrada que o Skill Engine invoca (implementação, nasce com o épico)
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
| `actions` | Pontos de entrada (ações) que o Plugin expõe ao Skill Engine |

## Relação com `/skills`

`/skills/*.md` documenta o *quê* e o *porquê* de cada Skill (contrato de domínio, casos de uso). `/plugins/*/manifest.json` documenta o *como* — o empacotamento técnico que o Skill Engine efetivamente carrega. Todo Plugin implementa exatamente uma Skill já especificada em `/skills`; não existe Plugin sem Skill correspondente documentada.

## Consequências para o Kernel

Ver [KERNEL.md](KERNEL.md): carregar Plugins pertence ao Kernel (via Skill Engine); conhecer a implementação de um Plugin específico nunca pertence.
