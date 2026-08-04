# Bootstrap

Como o SIGMA inicia — o design de referência da Release 2 (SIGMA Bootstrap, ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md)), escrito antes de qualquer código dela. Escopo estritamente de infraestrutura: Config, Logger, DI Container, Modules, Events (mecanismo), Lifecycle, Health. **Não cobre** Mission, IA/Agent, ou carregamento de Plugin — isso chega em Releases posteriores, sobre esta fundação.

## Como o SIGMA inicia

1. **Config** carrega variáveis de ambiente e arquivos de configuração por ambiente (local/homologação/produção), validando presença dos valores obrigatórios antes de qualquer outra coisa iniciar — falhar cedo e alto, nunca silenciosamente com um valor `null` se propagando.
2. **Logger** é inicializado em seguida — a partir deste ponto, toda falha de bootstrap já é registrada de forma estruturada (ver [TELEMETRY.md](TELEMETRY.md)), mesmo antes de qualquer Engine existir.
3. O **DI Container** é montado, registrando os bindings declarados por cada Module (ver abaixo).
4. Os **Modules** registrados são inicializados na ordem resolvida por suas dependências declaradas (ver "Como um módulo declara dependências de outro").
5. O **Health** endpoint fica disponível assim que todos os Modules obrigatórios reportam `ready`.

## Como um módulo é carregado

Um **Module**, nesta especificação, é a unidade que o Bootstrap conhece — cada pacote de `packages/` (ex: `kernel`, e mais tarde `mission-engine`, `planner-engine`...) se registra como um Module. Um Module declara:

```
Module {
  name: string
  dependsOn: string[]        // nomes de outros Modules
  register(container): void  // registra seus bindings no DI Container
  boot(): Promise<void>       // lógica de inicialização, após todos os bindings existirem
}
```

`register` acontece para todos os Modules antes de `boot` começar em qualquer um — garante que, quando um Module tenta resolver uma dependência durante seu `boot`, o binding já existe no Container, mesmo que o Module dono ainda não tenha terminado de inicializar.

## Como um Engine registra seus serviços

Um Engine (Release 3 em diante) é um Module que registra, no `register(container)`, os contratos que expõe a outros Engines (ex: o Mission Engine registra um `MissionRepository` que o Execution Engine pode resolver) — nunca uma implementação concreta importada diretamente por quem consome. Isso mantém o desacoplamento entre Engines mesmo dentro do mesmo processo/deploy.

## Como Plugins são descobertos

**Fora do escopo da Release 2.** O mecanismo de `Modules` descrito aqui é genérico o suficiente para, na Release 7 (Skill Engine), Plugins serem descobertos e registrados da mesma forma — um Plugin válido se torna, na prática, um Module cujo manifest (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) descreve seus bindings em vez de código TypeScript/PHP direto. A Release 2 não implementa esse carregamento; apenas não fecha portas para ele.

## Ciclo de vida (boot, start, ready, shutdown)

```
boot → start → ready → (operando) → shutdown
```

| Estado | Significado |
|---|---|
| `boot` | Config/Logger/Container montados; Modules sendo registrados |
| `start` | Todos os Modules chamando `boot()`; conexões (banco, Redis) sendo abertas |
| `ready` | Todos os Modules obrigatórios reportaram sucesso; Health responde `200` |
| `shutdown` | Sinal de encerramento recebido; Modules encerram na ordem inversa de dependência, drenando trabalho em andamento antes de fechar conexões |

Um Module que falha em `start` impede o sistema de chegar a `ready` — nenhum Engine opera parcialmente inicializado; o Health reflete isso como não pronto, não como um erro genérico.

## Injeção de dependências

O DI Container resolve dependências por contrato (interface), nunca por implementação concreta — consistente com Clean Architecture já definida em [ARCHITECTURE.md §1](docs/architecture/ARCHITECTURE.md). Escopo de vida de cada binding (singleton por processo vs. por request) é declarado pelo Module que o registra; o padrão é singleton, salvo declaração em contrário.

## Como um módulo declara dependências de outro

Pelo campo `dependsOn` no Module — uma lista de nomes de outros Modules que precisam estar registrados (não necessariamente `ready`) antes deste. O Bootstrap resolve a ordem topológica de inicialização a partir dessas declarações e falha imediatamente, com mensagem explícita, se detectar dependência circular — nunca tenta "adivinhar" uma ordem que funcione.

## Relação com KERNEL.md

[KERNEL.md](KERNEL.md) descreve o escopo conceitual completo do Kernel — incluindo carregamento de Plugin System e contexto de Tenant/Workspace, que este documento explicitamente não cobre. Este documento é o design da Release 2, o primeiro incremento daquele escopo — não sua totalidade. Ver [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md).
