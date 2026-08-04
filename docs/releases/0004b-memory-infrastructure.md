# Release 4B — Memory Infrastructure

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), seguindo o processo de quatro fases de [ADR-0048](../adr/0048-processo-quatro-fases.md) e o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) ([ADR-0082](../adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)). **Revisão 1 — aguardando aprovação do Product Owner.** Segunda metade da Release 4 — Memory Engine ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)), escrita agora que a [Release 4A — Memory Domain](0004a-memory-domain.md) está implementada e validada ([Decision Log](0004a-memory-domain-decision-log.md), [Validation Report](0004a-memory-domain-validation-report.md)). Nenhuma linha de código desta sub-Release é escrita antes de aprovação explícita — mesma disciplina de 4A e de toda Release anterior.

Ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md) (quatro camadas DDD), [ADR-0079](../adr/0079-usertwin-desde-a-release-4.md) (UserTwin desde já), [ADR-0080](../adr/0080-knowledge-release4-indice-simples.md) (Knowledge simples), [ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md)/[ADR-0084](../adr/0084-confidence-como-gate-de-promocao.md) (promoção), [ADR-0085](../adr/0085-digital-twin-estritamente-event-driven.md) (Twin Event-Driven), [ADR-0086](../adr/0086-knowledgerecord-imutavel-e-versionado.md) (Knowledge versionado), [ADR-0088](../adr/0088-retracao-expiracao-e-governanca-de-promocao.md) (governança), [MEMORY_MODEL.md](../../MEMORY_MODEL.md), [MEMORY_LIFECYCLE.md](../../MEMORY_LIFECYCLE.md), [MEMORY_PROMOTION_RULES.md](../../MEMORY_PROMOTION_RULES.md), `contracts/Memory.contract.yaml`.

## Objetivo

Dar alcance real ao domínio já modelado e testado em 4A: persistir as quatro entidades em MariaDB, indexar `/knowledge` de fato, e — o item mais exigente desta sub-Release — sincronizar `UserTwin` de verdade a partir dos eventos que o Identity Engine já publica, o que expõe e resolve uma lacuna real de infraestrutura ainda não enfrentada pelo projeto (ver "Arquitetura" abaixo).

## Achado que muda o escopo desta Proposal em relação ao placeholder

O placeholder anterior deste documento previa "consumo real de eventos Semantic do Event Bus" como um item de escopo comum, do mesmo porte dos outros. Não é. `services/event-bus`'s `RedisEventBus` (ver [ADR-0057](../adr/0057-eventbus-composicao-inmemory.md)) **publica de fato no canal Redis, mas só entrega localmente, dentro do mesmo processo** — `subscribe()` registra o handler só no `InMemoryEventBus` composto internamente. Não existe, em nenhum lugar do projeto hoje, um listener Redis real (`pubSubLoop`) que entregue um evento publicado por um processo (`services/auth`) a um handler registrado em **outro** processo. O Decision Log da Release 2 e o próprio ADR-0057 já sinalizavam isso conscientemente: "fica para quando houver um consumidor de verdade".

O Memory Engine, sincronizando `UserTwin` a partir de `identity.created`/`workspace.selected` publicados por `services/auth`, é exatamente esse primeiro consumidor de verdade — e roda, por definição, num processo diferente do `services/auth` (nenhum Engine roda dentro do processo de outro). Sem resolver isso, `UserTwin` continuaria sendo populado só em teste automatizado (Application chamada diretamente), nunca de fato em produção — o que contradiria a própria razão de [ADR-0079](../adr/0079-usertwin-desde-a-release-4.md) existir. Por isso esta Proposal inclui, como escopo central (não um detalhe de implementação), a construção do primeiro listener Redis cross-processo real do projeto.

## Escopo

**Existe:**

- `packages/memory-engine/src/Application/` — casos de uso sobre o `Domain/` já validado por 4A, sem conhecer MariaDB/HTTP/Redis diretamente: `StartContextMemory`, `AppendContextMemoryContent`, `CloseAndDistillContextMemory`, `EvaluateMemoryPromotion` (cobre os dois saltos), `MarkMemoryContradicted`, `ReactivateMemoryRecord`, `RetractMemoryRecord`, `PinMemorySubject`/`UnpinMemorySubject`, `IndexKnowledgeFromFile`, `ReviseKnowledgeFromFile`, `ProjectDigitalTwinFromEvent` (o handler que a Interfaces regista no Event Bus), `CheckDigitalTwinStaleness`.
- Interfaces de repositório declaradas em `Application/` (`ContextMemoryRepository`, `MemoryRecordRepository`, `KnowledgeRecordRepository`, `DigitalTwinRepository`) — implementadas por `Infrastructure/`, nunca o contrário. Mesmo padrão de `packages/identity-engine`.
- **`PinnedMemorySubject`** — um pequeno Value Object de `Application/` (não de `Domain/`, decisão deliberada — ver "Arquitetura" abaixo), representando "este `subjectKey`, neste Workspace, está fixado contra promoção automática, por este actor". Resolve a pendência deixada explicitamente em aberto pelo [Decision Log da 4A](0004a-memory-domain-decision-log.md).
- `packages/memory-engine/src/Infrastructure/` — implementação das interfaces acima sobre PDO puro (mesma decisão de `packages/identity-engine`, [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)), migrations para `context_memories`, `memory_records`, `knowledge_records`, `digital_twins`, `pinned_memory_subjects` — `tenant_id` obrigatório em todas ([ADR-0021](../adr/0021-multitenancy-desde-o-schema.md)). Indexador real de `/knowledge` (lê os arquivos Markdown de `knowledge/<area>/*.md`, popula `KnowledgeRecord` `version: 1`, ou nova versão se `sourcePath` já existir — busca textual simples via `LIKE`/full-text do MariaDB, [ADR-0080](../adr/0080-knowledge-release4-indice-simples.md)).
- **`services/event-bus/src/RedisSubscriber.php`** — o listener Redis cross-processo real que faltava (ver "Achado" acima e "Arquitetura" abaixo): um loop bloqueante (`pubSubLoop` do Predis) que traduz mensagens do canal Redis em chamadas aos handlers já registrados localmente via `IEventBus::subscribe()`.
- **`services/memory-worker`** — novo serviço deployável mínimo: no boot, registra `MemoryEngineModule` no Container (que por sua vez chama `IEventBus::subscribe('identity.created', ...)`/`subscribe('workspace.selected', ...)`), depois entra no loop bloqueante de `RedisSubscriber`. Sem porta HTTP — é um worker, não uma API.
- `packages/memory-engine/src/Interfaces/` — `MemoryEngineModule implements IModule` (`kind: Engine`, `dependsOn: ['kernel', 'event-bus']`), registrando os casos de uso/repositórios no Container e assinando os eventos do Identity Engine no `boot()`.
- `docker-compose.yml` ganha o serviço `memory-worker` — reaproveita o `mariadb` já existente (schema novo, não um banco/container novo).
- `system-manifest.yaml` ganha o Module `memory-engine`.

**Não existe ainda:**

- **API HTTP pública para o Memory Engine** (`services/memory`) — deliberadamente fora de escopo. Nesta Release, o Memory Engine ainda não tem nenhum consumidor humano/HTTP real (Mission/Planner/Agent Engine, que consultariam `MemoryRecord`/`DigitalTwin` para dar contexto a uma Subtask, ainda não existem). Expor uma API agora seria antecipar uma forma de consumo que ninguém usa — mesma disciplina de não antecipar Releases futuras já aplicada em toda Proposal anterior. A Scenario Validation desta Release prova a persistência via consulta direta ao repositório (dentro de um teste de integração contra a MariaDB real), não via `curl`.
- **Algoritmo de destilação de `ContextMemory` em `DistilledFact[]`** — extrair fatos de `rawContent` exige raciocínio sobre linguagem natural que nenhum Engine do SIGMA tem ainda (isso é trabalho de Agent/Skill Engine, Release 8+, ou de uma IA externa chamada por eles). `CloseAndDistillContextMemory` recebe a lista de `DistilledFact` já pronta como parâmetro — quem a produz, nesta Release, é sempre um caller explícito (um teste, ou futuramente um Agent), nunca o próprio Memory Engine.
- **Algoritmo de detecção de contradição** entre dois `MemoryRecord` do mesmo `subjectKey` — mesmo motivo acima. `MarkMemoryContradicted` recebe o `MemoryRecordId` contraditor já identificado por quem chama.
- **Algoritmo de generalização de `subjectKey`** (`client.brenno.x` → `client.*.x`) — mesmo motivo. `EvaluateMemoryPromotion` recebe a chave generalizada já calculada por quem chama, exatamente como o próprio `Domain/` já exige ([ADR-0081](../adr/0081-mecanica-de-promocao-de-memory.md)).
- Retry/dead-letter/at-least-once delivery para `RedisSubscriber` — o loop simples (reconecta se cair, processa uma mensagem por vez) é suficiente para o único consumidor real desta Release; hardening de mensageria fica para a Release 23 (Production Hardening, [ROADMAP.md](../../ROADMAP.md)).
- `ClientTwin`/`ProjectTwin`/`CompanyTwin` populados de fato — só `UserTwin`, conforme [ADR-0079](../adr/0079-usertwin-desde-a-release-4.md); os outros três ficam com repositório/schema prontos, sem nenhuma linha até a Release 8.

**Onde vive:**

- `packages/memory-engine/src/Application/`, `Infrastructure/`, `Interfaces/` — as três camadas que faltavam.
- `services/event-bus/src/RedisSubscriber.php` — novo, dentro do serviço já existente (é infraestrutura de Event Bus, não do Memory Engine).
- `services/memory-worker` — novo serviço deployável.
- `docker/docker-compose.yml` atualizado com `memory-worker`.

## Arquitetura

`Interfaces/MemoryEngineModule` só conhece as seis interfaces do Kernel API ([ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md)) — nunca `Predis\Client`/PDO diretamente. `Application/` só conhece `Domain/` e suas próprias interfaces de repositório — nunca MariaDB, nunca Redis diretamente. `Infrastructure/` implementa essas interfaces sobre PDO puro.

**`PinnedMemorySubject` vive em `Application/`, não em `Domain/`** — decisão que fecha a pendência da 4A. O Decision Log da 4A já explicava o porquê: fixar um `subjectKey` não muda o estado de nenhum `MemoryRecord` específico, não tem invariante de negócio que só o próprio Aggregate possa proteger — é puramente "existe ou não existe esta marca". Modelá-lo em `Domain/` teria sido abstração sem necessidade real (o mesmo julgamento já registrado na 4A); modelá-lo em `Application/`, como um Value Object simples com sua própria interface de repositório, resolve a persistência sem forçar um Aggregate artificial.

**O listener Redis cross-processo** (o item central desta Proposal): `RedisSubscriber::listen(array $events, callable $onMessage): never` usa `Predis\Client::pubSubLoop()` para bloquear indefinidamente, decodificando cada mensagem recebida e invocando `$onMessage($event, $payload)`. `services/memory-worker` monta o Kernel exatamente como `services/gateway`/`services/auth` já fazem (mesmo `Bootstrap`, mesmo System Manifest), mas em vez de expor HTTP, seu `public/index.php` (ou `bin/worker.php`, a confirmar na Implementation) chama `RedisSubscriber::listen(['identity.created', 'workspace.selected'], fn ($event, $payload) => $eventBus->dispatchLocally($event, $payload))` — onde `dispatchLocally` é um método novo, pequeno, que `RedisEventBus`/`InMemoryEventBus` precisam expor para permitir que um evento **recebido de fora** (não publicado por este processo) ainda dispare os handlers já registrados localmente via `subscribe()`, sem tentar publicar de volta no Redis (o que causaria eco). Esse pequeno ajuste em `packages/kernel`/`services/event-bus` é parte do escopo desta Proposal, não do Memory Engine em si — beneficia qualquer worker futuro (`services/audit-worker`, etc.).

**Migrations no Lifecycle**: mesmo padrão já usado por `IdentityEngineModule` — rodam dentro do `boot()`. A mesma característica já documentada no Decision Log/Validation Report da Release 3.5 (migrations só rodam na primeira operação que as aciona, não no startup do container) se repete aqui — aceita conscientemente, mesma decisão já tomada, não revisitada nesta Proposal.

## Dependências

- Release 4A — Memory Domain, implementada e validada.
- Release 3B — Identity Infrastructure (fonte real dos eventos `identity.created`/`workspace.selected` que este worker consome).
- Release 3.5 (`InMemoryEventBus` em `packages/kernel`, composição documentada em [ADR-0057](../adr/0057-eventbus-composicao-inmemory.md)) — base sobre a qual `RedisSubscriber` é construído.
- MariaDB disponível no ambiente (já existe via `docker-compose.yml` desde a Release 3B) — reaproveitado, não recriado.

## Riscos

1. **O listener Redis cross-processo é o primeiro do projeto — não há precedente interno para validar o design contra.** Mitigado por manter o escopo mínimo possível (um loop bloqueante, um worker, dois eventos consumidos) e por testar a entrega cross-processo de fato via `docker compose up` real (dois containers distintos, `auth` publicando e `memory-worker` consumindo), não só em teste automatizado de um processo só.
2. **`pubSubLoop` bloqueante pode não se comportar bem sob restart/reconexão de Redis** — mitigado por escopo explicitamente restrito (sem retry sofisticado nesta Release, ver "Não existe ainda"); qualquer instabilidade encontrada vira Pendência documentada, não bloqueia a Release se o caminho feliz funcionar.
3. **Escopo cresceu em relação ao placeholder original** (a descoberta do gap do listener Redis) — aceito conscientemente porque é pré-requisito real para `UserTwin` funcionar de fato, não um adicional opcional; sinalizado explicitamente nesta Proposal, não silenciosamente absorvido.
4. **`dispatchLocally` em `IEventBus`/`RedisEventBus` muda uma interface já em uso por dois Engines (Identity, Memory)** — mitigado por ser aditivo (novo método, não alteração de assinatura existente) e por já ter sido implicitamente previsto pelo próprio ADR-0057 como o próximo passo natural.
5. **Três algoritmos de raciocínio (destilação, contradição, generalização) continuam sem implementação real** — aceito conscientemente, mesmo já documentado na 4A; esta Release não piora nem resolve isso, só não finge resolver com um heurístico raso.

## Entregáveis

- `packages/memory-engine/src/Application/`, `Infrastructure/`, `Interfaces/` implementados.
- Migrations para as cinco tabelas.
- `services/event-bus/src/RedisSubscriber.php` + método `dispatchLocally` em `IEventBus`/implementações.
- `services/memory-worker` completo, sem porta HTTP.
- `docker/docker-compose.yml` com `memory-worker`, `docker compose up --build` validado de fato.
- `system-manifest.yaml` atualizado com o Module `memory-engine`.
- **Decision Log** (`docs/releases/0004b-memory-infrastructure-decision-log.md`).
- **Validation Report** (`docs/releases/0004b-memory-infrastructure-validation-report.md`), com a seção "Docker" provando a entrega cross-processo de fato (não só o build subindo).

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- `Application/`: testado com implementações em memória das interfaces de repositório (test doubles) — mesmo espírito da Release 3B.
- `Infrastructure/`: testado contra uma MariaDB real (via `docker-compose`) — round-trip de cada repositório, respeitando `tenant_id`.
- `RedisSubscriber`: testado contra um Redis real — publicar num "processo" (conexão) e confirmar que o handler registrado noutra instância de `RedisSubscriber` recebe a mensagem.
- Migrations aplicam limpo em um banco vazio; rodar duas vezes não falha.

### 2. Architecture Validation
- `Application/` não importa nenhuma classe de `Infrastructure/` (só as interfaces que ela mesma declara).
- `Interfaces/MemoryEngineModule` não importa PDO/Predis diretamente.
- `services/memory-worker` não importa nenhuma classe concreta do Kernel fora das seis interfaces.

### 3. Scenario Validation
- `docker compose up --build` real, com `memory-worker` como container separado de `auth` — subida completa validada.
- Login via `services/auth` (`POST /auth/login`) → `identity.created`/`workspace.selected` publicados no Redis real → `memory-worker`, em processo/container separado, recebe e persiste `UserTwin` — provado consultando a MariaDB diretamente (não via HTTP, já que não há API pública ainda).
- Reiniciar o container `memory-worker` não perde `UserTwin`s já persistidos (prova de que a persistência é real).
- Indexar um arquivo real de `/knowledge` produz um `KnowledgeRecord` consultável; indexar de novo o mesmo arquivo produz `version: 2`, preservando `version: 1`.

## Critérios de Aceite

- As cinco tabelas existem, com `tenant_id` obrigatório, migrations aplicadas via `docker-compose up --build` real.
- `MemoryEngineModule` sobe via Bootstrap sem exigir mudança no contrato `IModule` — ou, se exigir, a mudança é uma ADR nova.
- Um evento publicado por `services/auth` chega de fato a `services/memory-worker`, em containers/processos diferentes, e resulta num `UserTwin` persistido — provado via `docker compose up` real, não só teste automatizado de um processo.
- `KnowledgeRecord` real, indexado a partir de `/knowledge`, consultável e versionado.
- Os três níveis de validação executados e documentados no Validation Report — "Docker" prova a entrega cross-processo, não só o build subindo.

## Quando esta Proposal foi escrita

Assim que a [Release 4A — Memory Domain](0004a-memory-domain.md) teve Decision Log e Validation Report publicados — mesma rodada, seguindo diretamente a Implementation de 4A, a pedido do Product Owner ("pode seguir").
