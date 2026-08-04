# Memory Promotion Rules

Exigência única e explícita do Product Owner antes de qualquer código da Release 4: um documento respondendo, sem ambiguidade, as oito perguntas de governança sobre o ciclo de vida de uma Memory. Complementa [MEMORY_MODEL.md](MEMORY_MODEL.md) (as entidades) e [MEMORY_LIFECYCLE.md](MEMORY_LIFECYCLE.md) (o fluxo) — este documento é onde os números, os papéis e os limites vivem.

Decisões formalizadas em [ADR-0084](docs/adr/0084-confidence-como-gate-de-promocao.md) (o gate de `confidence`) e [ADR-0088](docs/adr/0088-retracao-expiracao-e-governanca-de-promocao.md) (retração, expiração, papéis).

## Quando uma Memory nasce

Na destilação de um `ContextMemory` fechado (ver [MEMORY_LIFECYCLE.md — Fluxo 1](MEMORY_LIFECYCLE.md#fluxo-1--do-engajamento-ao-memoryrecord)). Um fato só vira `MemoryRecord` `Operational` se `confidence ≥ 0.50` no momento da destilação — abaixo disso, é tratado como ruído e não é registrado (o `ContextMemory` de origem continua existindo até sua própria expiração, então nada é perdido de forma irrecuperável, só não é promovido a fato). `0.50` é o piso mais baixo dos três definidos neste documento — o mais fácil de atingir, porque criar um `Operational` é reversível e barato (pode nunca ser promovido, pode expirar).

## Quando uma Memory sobe

Dois saltos, cada um com seu próprio piso de `confidence` — quanto maior o impacto da promoção (mais Workspaces passam a "herdar" o fato), maior a confiança exigida:

| Salto | Condição estrutural | Piso de `confidence` |
|---|---|---|
| `Operational → Project` | Mesmo `subjectKey`, mesmo `workspaceId`, 2+ `missionId` distintas (`sourceMissionIds`) | `≥ 0.70` |
| `Project → LongTerm` | Mesmo `subjectKey` (ou generalizado), `Project` em 2+ `workspaceId` distintos | `≥ 0.90` |

Os dois exemplos do Product Owner se encaixam aqui: `0.35` não atinge nenhum piso — nem o de criação — e nunca vira sequer um `Operational`. `0.97` atinge os dois pisos, então um `subjectKey` com repetição e generalização suficientes promove direto na primeira avaliação em que a estrutura permitir (nunca pulando `Project`, ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-a-promoção-funciona)).

A estrutura (repetição/generalização) e a confiança são **dois gates independentes** — os dois precisam ser satisfeitos, nenhum substitui o outro. Estrutura sem confiança suficiente não promove (o padrão pode ser real, mas o Memory Engine ainda não está seguro o bastante). Confiança alta sem repetição/generalização também não promove (por mais que um único relato pareça certo, ele ainda é uma amostra de um só Workspace/uma só Mission).

Os pisos (`0.50`/`0.70`/`0.90`) são parâmetros de configuração do Memory Engine, não constantes de código — ajustáveis por Tenant na Implementation, começando com estes valores como padrão. Mudar o padrão global exige uma ADR nova (não uma edição silenciosa deste documento).

## Quando uma Memory desce

Um `MemoryRecord` nunca é rebaixado de nível *em lugar* — a escada Operational/Project/LongTerm só cresce para cima, sempre criando um registro novo (ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-a-promoção-funciona)). "Descer" significa o registro deixar de ser confiável, via o atributo `status`:

1. **Contradição automática → `Deprecated`.** Se uma nova observação, com `confidence` acima do piso de criação (`≥ 0.50`), tem o mesmo `subjectKey` de um `MemoryRecord` já `Active` mas contradiz seu `content`, o Memory Engine marca o registro existente como `Deprecated` automaticamente e publica `MemoryDeprecated` — nunca apaga o registro. `Deprecated` significa "sinalizado como possivelmente ultrapassado, ainda visível, não usado por padrão em consultas que priorizam confiabilidade".
2. **Retração definitiva → `Retracted`.** Só acontece por ação humana explícita (ver "Quem pode impedir" abaixo) — nunca automaticamente. Um registro `Retracted` continua existindo (nunca apagado — auditabilidade, mesmo princípio de `promotedFrom`/`previousVersionId`), mas é excluído de qualquer consulta padrão, inclusive como evidência para futuras promoções do mesmo `subjectKey`.
3. Um `MemoryRecord` `Deprecated` **pode voltar a `Active`** se uma observação seguinte reforçar o `content` original (mesmo `subjectKey`, sem nova contradição) — `Retracted` nunca volta sozinho, só por nova ação humana.

## Quando uma Memory expira

Políticas de retenção diferentes por entidade, porque o custo de manter cada uma é diferente:

- **`ContextMemory`**: retenção bruta de **30 dias corridos** após `status: Closed`, por padrão (configurável por Tenant, mesmo mecanismo dos pisos de `confidence` acima). Depois disso, `rawContent` é purgado — os `MemoryRecord` já destilados sobrevivem normalmente, só o material bruto de origem é descartado.
- **`MemoryRecord` `Operational` nunca promovido**: expira (`status: Expired`, tratado como não-evidência a partir daí) após **90 dias sem reforço** — nenhuma nova `missionId` reforçando o mesmo `subjectKey` no mesmo Workspace. Um fato que nunca se repetiu em três meses provavelmente não é um padrão. Uma observação nova do mesmo `subjectKey` depois da expiração começa um novo ciclo do zero, não reaproveita o registro expirado.
- **`MemoryRecord` `Project`/`LongTerm`**: **não expiram por tempo**. Representam padrão já confirmado por repetição/generalização — só saem de circulação por contradição (`Deprecated`/`Retracted`), nunca por idade. Revalidação periódica por idade é uma extensão possível, dependente do componente estrutural Scheduler — não antecipada nesta Release.
- **`KnowledgeRecord`**: nunca expira — é imutável e versionado (ver [MEMORY_MODEL.md](MEMORY_MODEL.md#knowledgerecord)); uma versão desatualizada continua existindo como histórico, mesmo depois de uma versão mais nova existir.
- **`DigitalTwin`**: nunca expira no sentido de ser apagado — fica `stale` (ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-o-digital-twin-é-sincronizado)), o que é uma condição diferente de expiração: um Twin stale ainda é a melhor informação disponível, só sinalizada como possivelmente desatualizada.

## Quando vira Knowledge

Nunca automaticamente. Um `MemoryRecord` que atinge `LongTerm` é sinalizado como **candidato** (evento `MemoryPromoted`, `toLevel: LongTerm`) — vira `KnowledgeRecord` de fato só através de uma ação humana deliberada via `/knowledge`, mesmo fluxo de curadoria de sempre (ver [MEMORY_MODEL.md — Fronteira com Knowledge](MEMORY_MODEL.md#fronteira-com-knowledge--candidatura-nunca-promoção-automática)). Um `MemoryRecord` `Deprecated`/`Retracted` nunca é elegível como candidato, mesmo que tenha atingido `LongTerm` antes de ser contradito.

## Quando vira Twin

Nunca. `DigitalTwin` não nasce de `MemoryRecord`/`KnowledgeRecord` — é um pipeline paralelo e independente, alimentado exclusivamente por Semantic Events (`Evento → Projection → Twin`, ver [MEMORY_MODEL.md — Como o Digital Twin é sincronizado](MEMORY_MODEL.md#como-o-digital-twin-é-sincronizado)). Os dois pipelines compartilham custodiante (Memory Engine) e frequentemente falam da mesma entidade de negócio (ex: um cliente tem `MemoryRecord`s experienciais **e** um `ClientTwin` factual), mas um nunca se converte no outro.

## Quem pode promover

Por padrão, ninguém — é o Memory Engine, através de `EvaluatePromotion`, aplicando as regras estruturais e os pisos de `confidence` acima, sempre que a operação é chamada (sob demanda nesta Release, ver [MEMORY_MODEL.md](MEMORY_MODEL.md#como-a-promoção-funciona)). Nenhum humano promove um `MemoryRecord` diretamente no fluxo normal.

Exceção: um User com a Permission `memory.promote` ([formato de chave já estabelecido em IDENTITY_MODEL.md](IDENTITY_MODEL.md#permission)) pode forçar a promoção de um `MemoryRecord` específico fora dos pisos automáticos — para os casos em que um humano já sabe que um fato é verdadeiro e não quer esperar a repetição/generalização natural. Toda promoção forçada é registrada com o `actor` que a executou (metadata padrão de evento, [ADR-0076](docs/adr/0076-metadata-padrao-em-eventos-de-dominio.md)) e é sempre auditável — nunca indistinguível de uma promoção automática no histórico.

## Quem pode impedir

Um User com a Permission `memory.block_promotion` pode:

1. **Fixar (`pin`) um `subjectKey`** para nunca promover automaticamente, independentemente de repetição/generalização/`confidence` — útil para dados sensíveis ou pessoais que nunca devem virar padrão institucional, mesmo que se repitam.
2. **Retratar (`Retracted`)** um `MemoryRecord` já promovido — a única forma de um registro chegar a `Retracted` (ver "Quando uma Memory desce" acima).

Ambas as ações são publicadas como evento, com `actor`, e nunca silenciosas.

## Quem revisa

Duas camadas de revisão, com finalidades diferentes:

1. **Audit Engine (automática, sempre)** — consome todo evento de Memory (`MemoryRecordObserved`, `MemoryPromoted`, `MemoryDeprecated`, e os dois eventos de intervenção humana acima) para a trilha de conformidade — "o que o SIGMA aprendeu, quando, e quem interferiu". Isso já é o papel do Audit Engine para todo o sistema (ver [EVENT_CATALOG.md](EVENT_CATALOG.md)), não um mecanismo novo.
2. **Humana, na candidatura a Knowledge** — todo `MemoryRecord` `LongTerm` sinalizado como candidato é revisado por um User com a Permission `knowledge.curate` antes de virar `KnowledgeRecord` real, mesmo fluxo de curadoria de `/knowledge` já existente. Esta é a revisão que efetivamente decide "isso vira conhecimento institucional ou não" — nunca automática (ver "Quando vira Knowledge" acima).

## O que este documento não decide

- O algoritmo que calcula `confidence` no momento da destilação de um `ContextMemory` — decisão de Implementation da Release 4B (provavelmente heurística nesta Release, sem modelo de linguagem dedicado — a escolha exata fica para a Proposal de 4B).
- O algoritmo de detecção de contradição entre dois `MemoryRecord` do mesmo `subjectKey` — mesma observação, decisão de Implementation.
- A interface de revisão de candidatos a Knowledge (fila, notificação, tela) — Implementation/Interfaces.
- Ajuste dos pisos padrão (`0.50`/`0.70`/`0.90`) por Tenant — mecanismo de configuração é Implementation; o valor inicial é o que este documento fixa.

## Onde vive

Consultado por `packages/memory-engine/src/Domain/` (a lógica de `EvaluatePromotion` implementa exatamente estas regras) e por `packages/memory-engine/src/Application/` (4B, onde `memory.promote`/`memory.block_promotion`/`knowledge.curate` são de fato checadas contra o Context resolvido pelo Identity Engine).
