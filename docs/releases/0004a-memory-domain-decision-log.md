# Release 4A — Memory Domain — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0004a-memory-domain.md](0004a-memory-domain.md) (revisão 2). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

- `packages/memory-engine/src/Domain/` — `Identifier` (base própria, não compartilhada com o Identity Engine — ver "Por que `Identifier` não foi movida para `packages/core`" abaixo) e sete classes concretas: `ContextMemoryId`, `MemoryRecordId`, `KnowledgeRecordId`, `DigitalTwinId` (identidade própria) e `TenantId`, `WorkspaceId`, `MissionId` (referências opacas a agregados de outros Engines, [ADR-0039](../adr/0039-identity-engine.md)).
- Enums: `MemoryLevel`, `MemoryRecordStatus`, `ContextMemoryStatus`, `TwinSubjectType`.
- Os quatro Aggregates de [MEMORY_MODEL.md](../../MEMORY_MODEL.md): `ContextMemory`, `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin`.
- `packages/memory-engine/src/Domain/Event/` — os doze eventos de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md#memory-engine) (onze já catalogados nesta rodada + `MemoryReactivated`, encontrado e catalogado durante esta própria Implementation — ver "Achado real" abaixo), cada um `final`, implementando `DomainEvent`.
- `RecordsDomainEvents` — mesmo trait de `packages/identity-engine`, cópia própria (mesma decisão de não compartilhar código entre Engines nesta Release).
- `DistilledFact` — pequeno Value Object de suporte (não um dos quatro Aggregates do modelo) representando um fato já resolvido, pronto para virar `MemoryRecord` na destilação de um `ContextMemory`.
- **35 testes automatizados**, cobrindo cada Aggregate, a mecânica de promoção gated por `confidence`, contradição/reativação/retração, versionamento de Knowledge, staleness de Twin, e um cenário de engajamento completo ponta a ponta.

**Nenhuma dependência de infraestrutura** — `composer.json` só declara `sigma/core` (identificadores/exceções) como dependência de produção, mesmo padrão da Release 3A.

## Decisões locais e o porquê

### Por que `Identifier` não foi movida para `packages/core`

A Proposal 4A sinalizava isso como pergunta em aberto para a Architecture Review, com uma recomendação (mover, mesmo raciocínio do Envelope na Release 3.5). A aprovação desta rodada ("aprovado e pode seguir") não endereçou a pergunta explicitamente. Diante disso — e seguindo a disciplina de "tudo que for dúvida, sinalizar em vez de decidir sozinho" — a escolha de menor risco foi: `packages/memory-engine` ganha sua própria cópia de `Identifier` (código idêntico ao de `packages/identity-engine`, só o namespace muda), sem tocar no Identity Engine já validado em produção. A consolidação para `packages/core` continua recomendada e permanece sinalizada em `memory/NEXT.md` para uma futura decisão explícita — não foi descartada, só não executada sem confirmação direta, dado que exigiria alterar um Engine já shippado fora do escopo desta Release.

### `TenantId`/`WorkspaceId`/`MissionId` são cópias locais, referências opacas — nunca os tipos do Identity Engine

Mesmo raciocínio da decisão acima, mas por um motivo estrutural adicional: mesmo que `packages/memory-engine` importasse os tipos concretos do Identity Engine, isso criaria uma dependência de compilação de um Engine sobre outro — o que o próprio modelo já proíbe em prosa ([ADR-0039](../adr/0039-identity-engine.md): "Memory Engine... nunca resolve Tenant/Company/Workspace por conta própria"). O padrão DDD correto para uma referência cross-bounded-context é o bounded context de destino cunhar seu próprio tipo de identificador — ainda que o valor de string seja o mesmo. `MissionId` também nasce aqui, mesmo o Mission Engine não tendo Domain próprio ainda — é só uma referência opaca, não uma antecipação do modelo de Mission.

### `ContextMemory::distill()` recebe fatos já resolvidos (`DistilledFact`), nunca calcula `confidence` sozinho

A Proposal já declarava isso explicitamente na seção Arquitetura ("o algoritmo real de destilação... fica para a Release 4B"). `DistilledFact` é o tipo que formaliza essa fronteira: um Value Object simples (`subjectKey`/`content`/`confidence`), validado (`confidence` em `[0.0, 1.0]`), que a Application da Release 4B vai produzir a partir de `rawContent` usando o algoritmo que ainda não existe. `Domain/` só decide *o que fazer* com fatos já resolvidos (filtrar pelo piso, criar o `MemoryRecord`), nunca *como* resolvê-los.

### `MemoryRecord::evaluatePromotionToProject()`/`evaluatePromotionToLongTerm()` recebem a evidência estrutural já resolvida pelo chamador

Mesmo padrão já usado na Release 3A para `Identity::resolveContext()` (recebe `roleAssignments` já filtrados, nunca consulta um repositório). Aqui: quem chama já sabe (via consulta de repositório, Application 4B) quais outras Missions/Workspaces reforçam o mesmo `subjectKey` — o método só decide *se* isso, somado ao que o próprio registro já sabe (`sourceMissionIds`, seu `workspaceId`), mais o piso de `confidence`, é suficiente para promover. Retorna `null` (não lança exceção) quando a estrutura ou a confiança são insuficientes — isso é o resultado esperado do dia a dia, não um erro; lança `SigmaException` só quando o nível de origem está errado (ex: tentar promover um `LongTerm` a `Project`), o que é de fato um uso incorreto da API.

### `evaluatePromotionToLongTerm()` recebe a `subjectKey` generalizada como parâmetro, não a calcula

[ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md) já documentava a generalização (`client.brenno.x` → `client.*.x`) como algo cuja implementação exata (Risco 1 da Proposal) poderia exigir lógica mais sofisticada que comparação de string. Resolvido aqui: o `Domain/` não gera a chave generalizada — recebe-a já pronta de quem chama, junto da lista de Workspaces reforçando o padrão. O novo `MemoryRecord` `LongTerm` é criado com essa chave generalizada, não com a `subjectKey` original — é a consequência direta de [ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md) dizer "o padrão deixa de ser sobre um cliente específico e passa a ser sobre um comportamento de negócio": o registro resultante precisa refletir isso no próprio `subjectKey`, não só no `level`.

### Achado real durante a Implementation: faltava um evento para "Deprecated volta a Active"

`MEMORY_PROMOTION_RULES.md` já descrevia essa transição ("um `MemoryRecord` `Deprecated` pode voltar a `Active` se uma observação seguinte reforçar o `content` original"), mas nenhum evento havia sido catalogado para ela — um gap real encontrado só ao escrever `MemoryRecord::reactivate()` e perceber que a transição, como qualquer outra mudança de estado relevante do domínio (ADR-0018 — "Tudo é Evento"), precisava de um. Corrigido catalogando `MemoryReactivated` (`memory.reactivated`) em `DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`/`contracts/Memory.contract.yaml` **antes** de escrever a classe de evento — mesma disciplina de "catalogar antes do código", aplicada dentro da própria Implementation quando o gap apareceu, não depois.

### `MemorySubjectPinned` é catalogado, mas não produzido por nenhum método de Aggregate nesta sub-Release

Diferente dos outros onze eventos, fixar (`pin`) um `subjectKey` contra promoção automática não muda o estado de nenhum `MemoryRecord` específico — é uma ação de governança sobre uma chave, não sobre um registro (o modelo não define um `pinned: bool` em `MemoryRecord`, nem deveria: um `subjectKey` pinado bloqueia *futuros* registros também, não só os existentes). Criar um Aggregate artificial só para produzir este evento teria sido abstração além do necessário — a decisão foi deixar a classe de evento existir (satisfaz "os doze eventos existem como classes de evento" do Critério de Aceite) e documentar que a Application (4B), ao checar a Permission `memory.block_promotion`, publica este evento diretamente, sem passar por um método de `Domain/`. Sinalizado explicitamente aqui em vez de forçar uma modelagem que não existe em [MEMORY_MODEL.md](../../MEMORY_MODEL.md).

### `KnowledgeRecord::reviseFrom()` é estático, recebendo a versão anterior como parâmetro — não um método de instância

Como `KnowledgeRecord` é imutável (ADR-0086), não existe "editar este objeto" — existe "criar o próximo, a partir deste". Um método estático que recebe `self $previous` deixa essa assimetria explícita na própria assinatura, em vez de um método de instância `$record->revise(...)` que sugeriria mutação do próprio objeto.

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 35 testes, `composer test` — todos passando (103 assertions), sem nenhum warning/deprecation.
2. **Architecture Validation**: nenhuma classe em `Domain/` importa `Application/`/`Infrastructure/`/`Interfaces/` (nenhuma dessas pastas existe ainda), nem `packages/kernel`, nem nenhuma biblioteca de banco/HTTP — `composer.json` só declara `sigma/core`. `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin` não se referenciam entre si em nenhum ponto — a única referência cruzada real é `ContextMemory` → `MemoryRecord` (via `distill()`), que é exatamente o relacionamento de destilação já modelado em `MEMORY_MODEL.md` (`CONTEXT_MEMORY ||--o{ MEMORY_RECORD : destila`), não uma violação do critério original (que falava dos três aggregates da revisão 1). Todo identificador em toda assinatura pública é um `Identifier` concreto, nunca `string`.
3. **Scenario Validation**: ver [Validation Report](0004a-memory-domain-validation-report.md).

## Impacto para a Release 4B

- `Application`/`Infrastructure`/`Interfaces` de `packages/memory-engine` vão consumir exatamente estas classes de `Domain/` — nenhuma foi desenhada pensando em persistência, mesmo objetivo que a divisão 4A/4B pretendia provar.
- A 4B precisa decidir: o algoritmo de destilação (`ContextMemory` → lista de `DistilledFact`), o algoritmo de detecção de contradição (quando marcar `markContradicted()`), o algoritmo de generalização de `subjectKey`, e onde a consulta de "quais outras Missions/Workspaces reforçam este `subjectKey`" acontece (repositório de qual camada).
- `MemorySubjectPinned` precisa de um lugar para persistir o estado "este `subjectKey` está pinado" — não existe um Aggregate para isso ainda; a 4B decide se cria um (`PinnedSubject`) ou se resolve de outra forma (ex: uma tabela simples sem Aggregate de domínio, dado que a única regra de negócio é "existe ou não existe").
- A pergunta em aberto sobre mover `Identifier` para `packages/core` continua sem decisão — sinalizada em `memory/NEXT.md`, não resolvida nesta Implementation.
