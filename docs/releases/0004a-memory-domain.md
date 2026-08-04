# Release 4A — Memory Domain

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md). **Revisão 1 — aguardando aprovação do Product Owner.** Primeira metade da Release 4 — Memory Engine, dividida em duas sub-Releases sequenciais por decisão explícita, mesmo padrão da Release 3 ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)): esta Proposal (4A) cobre só o **Domain**; a segunda metade (4B — Memory Infrastructure, ainda não escrita) só começa depois que 4A estiver implementada e validada.

O Product Owner declarou a Release 4 "o segundo marco mais importante do projeto, depois da Foundation" — praticamente todo Engine seguinte (Mission, Intent, Planner, Agent, Council) depende da qualidade do que ela expuser — e recomendou investir tempo extra na modelagem, mesmo que atrase o cronograma (ver [ADR-0070](../adr/0070-roadmap-estendido-24-releases.md)). Esta Proposal, e o modelo que a precede, seguem essa recomendação: nenhuma linha de código do Memory Engine é escrita antes de três aprovações explícitas e separadas: (1) [MEMORY_MODEL.md](../../MEMORY_MODEL.md); (2) [MEMORY_LIFECYCLE.md](../../MEMORY_LIFECYCLE.md); (3) esta Proposal.

Ver [ADR-0039](../adr/0039-identity-engine.md) (Memory Engine responde "o que sei/aprendi", distinto de Identity), [ADR-0022](../adr/0022-memory-em-tres-niveis.md) (três níveis de Memory), [ADR-0079](../adr/0079-usertwin-desde-a-release-4.md) (UserTwin desde já), [ADR-0080](../adr/0080-knowledge-release4-indice-simples.md) (Knowledge simples nesta Release), [ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md) (mecânica de promoção), [MEMORY_ARCHITECTURE.md](../../MEMORY_ARCHITECTURE.md), [DIGITAL_TWIN.md](../../DIGITAL_TWIN.md), [MEMORY_MODEL.md](../../MEMORY_MODEL.md), [MEMORY_LIFECYCLE.md](../../MEMORY_LIFECYCLE.md) e `contracts/Memory.contract.yaml` (design de referência que esta proposta implementa).

## Objetivo

Modelar completamente o domínio de Memory, Knowledge e Digital Twin em código puro — Value Objects, Entities/Aggregates (`MemoryRecord`, `KnowledgeRecord`, `DigitalTwin`), eventos de domínio, e as duas regras de negócio que [ADR-0022](../adr/0022-memory-em-tres-niveis.md)/[DIGITAL_TWIN.md](../../DIGITAL_TWIN.md) deixaram em aberto (promoção entre níveis, staleness de Twin) — sem nenhuma dependência de persistência, banco ou infraestrutura. Mesmo objetivo e mesma disciplina da Release 3A: provar que o modelo é implementável e consistente antes de qualquer schema de banco ser desenhado.

## Escopo

**Existe:**
- `packages/memory-engine/src/Domain/` (ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)) — só esta camada nesta sub-Release.
- Value Objects para todo identificador ([ADR-0063](../adr/0063-identificadores-como-value-objects.md)): `MemoryRecordId`, `KnowledgeRecordId`, `DigitalTwinId` — reaproveitando a mesma classe base `Identifier` já criada em `packages/identity-engine/src/Domain/Identifier.php` (mover para `packages/core` é uma pergunta em aberto desta Proposal, ver "Arquitetura" abaixo).
- Enums: `MemoryLevel` (`Operational`/`Project`/`LongTerm`), `TwinSubjectType` (`Client`/`Project`/`Company`/`User`).
- Aggregates de [MEMORY_MODEL.md](../../MEMORY_MODEL.md): `MemoryRecord` (com a lógica de promoção — [ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md)), `KnowledgeRecord`, `DigitalTwin` (com a lógica de staleness).
- Os seis eventos de domínio de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md#memory-engine), como classes de evento reais — produzidos por método de domínio, nunca publicados diretamente pelo `Domain/` (mesmo princípio de [ADR-0062](../adr/0062-identity-nunca-conhece-outro-engine.md), agora aplicado ao Memory Engine).
- `contracts/Memory.contract.yaml` já publicado (nesta mesma rodada) — validado contra a implementação real ao final desta sub-Release.

**Não existe ainda (fica para a Release 4B):** `Application/`, `Infrastructure/`, `Interfaces/` do Memory Engine — nada de persistência, repositório, `MemoryEngineModule`/`IModule`, consumo real de eventos do Event Bus, população de fato de nenhum Twin (nem `UserTwin` — o mecanismo é modelado e testado em memória nesta sub-Release, a sincronização real com eventos do Identity Engine é 4B). Indexação real de `/knowledge` também é 4B (Infrastructure).

**Onde vive:**
- `packages/memory-engine/src/Domain/` — única pasta nova desta sub-Release.

## Arquitetura

Domain puro, sem dependência de framework, banco, HTTP ou do Kernel — mesmo padrão de `packages/identity-engine/src/Domain/` na Release 3A. `MemoryRecord::promote()` (ou equivalente) recebe os dados necessários para avaliar repetição/generalização já resolvidos por quem chama (a `Application` de 4B, que consulta o repositório) — o aggregate decide *se* promove, dado o que já sabe, nunca consulta um repositório por conta própria.

**Pergunta em aberto para a Architecture Review** (não decidida nesta Proposal, deliberadamente): `Identifier` (a base abstrata de Value Object de identificador, hoje em `packages/identity-engine/src/Domain/Identifier.php`) deveria mover para `packages/core`, já que o Memory Engine precisa exatamente do mesmo mecanismo para seus próprios identificadores. Duas opções: (a) mover `Identifier` para `packages/core/src/Identifier.php`, `packages/identity-engine` passa a depender de `packages/core` para isso (já depende para `Id`/`SigmaException`/`Envelope`); (b) `packages/memory-engine` duplica sua própria base `Identifier`. Recomendação: opção (a), mesmo raciocínio que já moveu `Envelope` para `packages/core` na Release 3.5 ([ADR-0069](../adr/0069-envelope-em-packages-core.md)) — mas isso é uma recomendação, não uma decisão já tomada nesta Proposal.

## Dependências

- Release 3 (3A + 3B) e Release 3.5 — implementadas, validadas, aprovadas.
- [MEMORY_MODEL.md](../../MEMORY_MODEL.md), [MEMORY_LIFECYCLE.md](../../MEMORY_LIFECYCLE.md) aprovados — bloqueante, sem exceção.
- Nenhuma dependência de infraestrutura — mesma característica da Release 3A.

## Riscos

1. **A mecânica de promoção ([ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md)) pode se revelar difícil de implementar como código puro**, especialmente a generalização de `subjectKey` (`client.brenno.x` → `client.*.x`) — se a normalização exigir alguma forma de correspondência mais sofisticada do que comparação de string, isso pode empurrar lógica para fora do `Domain/`. Mitigado por testar exatamente esse caso na Implementation, documentando qualquer ajuste necessário ao modelo como ADR nova.
2. **`MemoryRecord`/`KnowledgeRecord`/`DigitalTwin` são três aggregates, não um só** (diferente da Release 3A, que tinha `Identity` como raiz clara) — risco de acoplamento acidental entre eles dentro do `Domain/`. Mitigado por Architecture Validation explícita verificando que nenhum dos três referencia os outros dois diretamente.
3. **Escopo pode crescer organicamente** dado que Memory Engine é declarado tão central — mitigado pela lista fechada de "Escopo" acima, mesmo padrão disciplinar já usado em toda Release anterior.

## Entregáveis

- `packages/memory-engine/src/Domain/` implementado — Value Objects, Enums, os três Aggregates, os seis eventos de domínio.
- Testes de unidade cobrindo `MEMORY_MODEL.md`/`MEMORY_LIFECYCLE.md` — promoção (repetição e generalização), staleness de Twin, invariantes de cada Aggregate — sem nenhuma dependência de infraestrutura.
- **Decision Log** (`docs/releases/0004a-memory-domain-decision-log.md`).
- **Validation Report** (`docs/releases/0004a-memory-domain-validation-report.md`) — seções "Docker"/"HTTP" preenchidas como "Não aplicável a esta sub-Release", mesmo padrão da 3A.

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- Um `MemoryRecord` `Operational` promovido a `Project` quando o mesmo `subjectKey` aparece em duas Missions do mesmo Workspace.
- Um `MemoryRecord` `Project` promovido a `LongTerm` quando o mesmo `subjectKey` (generalizado) aparece em Workspaces diferentes.
- Uma promoção nunca apaga o registro de origem — `promotedFrom` sempre rastreável.
- Nenhuma promoção direta de `Operational` para `LongTerm`.
- Um `DigitalTwin` fora da janela de refresh é identificável como stale (o cálculo de janela em si; o disparo do `warning` no Envelope é responsabilidade de 4B/Interfaces).
- Cada evento de [DOMAIN_EVENTS.md#memory-engine](../../DOMAIN_EVENTS.md#memory-engine) é produzido pela transição de domínio correta.

### 2. Architecture Validation
- Nenhuma classe em `Domain/` importa `Application/`/`Infrastructure/`/`Interfaces/`, nem `packages/kernel`, nem biblioteca de banco/HTTP.
- `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin` não se referenciam diretamente uns aos outros.
- Todo identificador é um Value Object.

### 3. Scenario Validation
- Um `subjectKey` observado em três Missions diferentes dentro do mesmo Workspace — promovido a `Project` só uma vez (idempotência da promoção).
- Um `KnowledgeRecord` e um `MemoryRecord` nunca compartilham a mesma identidade de aggregate mesmo tratando do mesmo assunto (ex: um cliente) — reforça que são conceitos distintos, não uma tabela genérica disfarçada.

## Critérios de Aceite

- `MemoryRecord`, `KnowledgeRecord`, `DigitalTwin` existem como código em `Domain/`, com a mecânica de promoção e de staleness implementadas exatamente como modeladas em [MEMORY_MODEL.md](../../MEMORY_MODEL.md)/[MEMORY_LIFECYCLE.md](../../MEMORY_LIFECYCLE.md).
- Todo identificador é um Value Object.
- Os seis eventos de [DOMAIN_EVENTS.md#memory-engine](../../DOMAIN_EVENTS.md#memory-engine) existem como classes de evento, produzidos corretamente.
- 100% dos testes desta sub-Release rodam sem nenhuma infraestrutura.
- Os três níveis de validação executados e documentados no Validation Report de 4A.
