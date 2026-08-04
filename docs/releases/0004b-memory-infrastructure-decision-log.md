# Release 4B — Memory Infrastructure — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0004b-memory-infrastructure.md](0004b-memory-infrastructure.md) (revisão 1). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

- `packages/memory-engine/src/Application/` — cinco interfaces de repositório, `PinnedMemorySubject` (Value Object, não Aggregate), treze casos de uso cobrindo todo o ciclo descrito em `MEMORY_LIFECYCLE.md`/`MEMORY_PROMOTION_RULES.md`.
- `packages/memory-engine/src/Infrastructure/` — `MigrationRunner`+`CreateSchema` (cinco tabelas + uma de junção `memory_record_source_missions`), cinco repositórios `Pdo*`, `KnowledgeFolderIndexer` (indexação real de `/knowledge`).
- `packages/memory-engine/src/Interfaces/MemoryEngineModule implements IModule` — segundo `IModule` de domínio real do SIGMA.
- **`services/event-bus/src/RedisSubscriber.php`** + `RedisEventBus::dispatchLocally()` — o primeiro listener Redis cross-processo real do projeto (ver "Achado" na Proposal).
- **`services/memory-worker`** — novo serviço deployável, sem HTTP.
- `docker/memory-worker.Dockerfile`, `docker-compose.yml` atualizado, `system-manifest.yaml` com o Module `memory-engine`.
- **60 testes automatizados novos** (47 em `packages/memory-engine` Application/Infrastructure + 1 em `services/memory-worker` + 4 em `services/event-bus`, contando `dispatchLocally` e `RedisSubscriber::handleMessage`) — 195 testes no monorepo, todos passando; rodados também contra MariaDB real via Docker (0 skips nessa configuração).
- **`docker compose up --build` real, com dois containers distintos (`auth` publicando, `memory-worker` consumindo) — `UserTwin` sincronizado de fato, cross-processo, provado por consulta direta à MariaDB.**

## Decisões locais e o porquê

### `PinnedMemorySubject` vive em `Application/`, não em `Domain/`

Já anunciado na Proposal — fecha a pendência explícita da 4A. Sem invariante de negócio própria (não protege nada que só um Aggregate possa proteger), é puramente "existe ou não existe esta marca". `PdoPinnedMemorySubjectRepository` usa `subject_key + workspace_id` como chave primária composta — natural para o caso de uso (uma marca por par).

### `TenantId`/`WorkspaceId`/`MissionId` continuam sendo cópias locais do Memory Engine (não do Identity Engine)

Mesma decisão da 4A, agora aplicada ao schema físico também: `context_memories`/`memory_records`/etc. armazenam esses IDs como `CHAR(36)` sem `FOREIGN KEY` para as tabelas do Identity Engine — são referências opacas de outro bounded context, não linhas que este schema possa (ou deva) validar por FK.

### `findReinforcingWorkspaceIds()` faz correspondência exata de string, nunca um padrão generalizado

A Proposal já declarava isso ("Não existe ainda: algoritmo de generalização de subjectKey"). O método existe e é testado (inclusive contra MariaDB real), mas só encontra repetição do **mesmo** `subjectKey` em Workspaces diferentes — nunca `client.*.x` casando com `client.brenno.x`/`client.nonalu.x`. Quem chama `EvaluateMemoryPromotion::toLongTerm()` decide a `subjectKey` generalizada do registro resultante; a busca por reforço continua exata.

### `ProjectDigitalTwinFromEvent` usa `identityId` como `externalRef`, não `userId`

Refinamento real, encontrado ao ligar os dois eventos de fato: `workspace.selected` carrega `identityId` (nunca `userId`) no payload mínimo já catalogado em [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md#identity-engine). Como os dois eventos precisam correlacionar com o **mesmo** `UserTwin`, e só `identityId` está presente nos dois, a chave de correlação precisou mudar. `identityId` e `userId` são 1:1 (`Identity::create()` sempre vincula exatamente um `User`) e igualmente internos ao SIGMA — a troca preserva o espírito da decisão original de MEMORY_MODEL.md ("nunca um id de sistema externo"), só corrige qual dos dois IDs internos é o correto.

### `DigitalTwinRepository::findBySubjectTypeAndExternalRef()` não filtra por Tenant

Já sinalizado na interface desde a 4A/4B — `externalRef` é sempre um UUID (`Sigma\Core\Id::generate()`), globalmente único por construção. Filtrar por `tenant_id` nesta consulta específica seria redundante (nunca muda o resultado) e exigiria que o chamador já soubesse o `tenantId` antes de encontrar o Twin — que é exatamente o dado que `workspace.selected` não carrega. Todas as outras consultas do Memory Engine continuam filtrando por Tenant normalmente (ADR-0021); esta é uma exceção pontual e justificada, não um precedente geral.

### `handleWorkspaceSelected()` ignora silenciosamente entrega fora de ordem

Se `workspace.selected` chegar antes de `identity.created` ter sido processado (não deveria acontecer na prática — mesmo processo publica os dois em sequência — mas a entrega via Redis não garante ordem entre processos diferentes), o handler não encontra o Twin e retorna sem erro, sem retry. Lançar exceção aqui derrubaria `services/memory-worker` inteiro por uma mensagem, não só ignorá-la — pior que perder uma atualização de estado. Sinalizado como Pendência no Validation Report; um mecanismo de retry/fila é hardening de mensageria (Release 23), fora de escopo aqui.

### `MemorySubjectPinned` não ganhou tratamento especial na Infrastructure

Confirma a decisão da 4A: `PinMemorySubject`/`UnpinMemorySubject` publicam o evento diretamente via `IEventBus`, sem passar por um handler de projeção — é uma ação de comando, não uma reação a um evento externo, então não precisa de nenhum `subscribe()` em `MemoryEngineModule`.

### Achado real durante a validação via Docker: o worker morria após ~60 segundos ociosos

**O achado mais importante desta sub-Release.** A primeira subida real (`docker compose up`) do `memory-worker` funcionou — registrou os handlers, imprimiu "pronto" — mas o processo morria sozinho, com `Predis\Connection\ConnectionException`, antes de qualquer evento chegar, sempre que ficava ocioso por tempo suficiente (o timeout padrão de socket do PHP, ~60s). A causa: `Predis\Client` sem `read_write_timeout` explícito usa o timeout padrão do PHP para o socket — adequado para request/response, fatal para uma conexão que fica bloqueada em `pubSubLoop()` esperando por uma mensagem que pode não chegar por minutos. Corrigido com `'read_write_timeout' => -1` na conexão dedicada de `bin/worker.php` (desativa o timeout de leitura/escrita no Predis — ver `StreamConnection::establishConnection()`). Sem este ajuste, o worker seria inutilizável em produção fora de um teste rápido — exatamente o tipo de achado que só aparece testando a arquitetura de verdade, não só o código (mesmo espírito do achado de migrations lazy na Release 3.5).

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 60 testes novos, 195 no monorepo — todos passando; rodados também contra MariaDB real via Docker (0 skips).
2. **Architecture Validation**: `Application/` não importa `Infrastructure/` nem PDO/Predis diretamente; `Interfaces/MemoryEngineModule` só constrói `\PDO` concretamente no próprio `register()` (mesmo padrão de `IdentityEngineModule`), nunca vaza para fora; `services/memory-worker` só conhece `RedisEventBus`/`RedisSubscriber` concretamente no seu próprio `bin/worker.php` (composition root), nunca dentro de `MemoryEngineModule`.
3. **Scenario Validation**: ver [Validation Report](0004b-memory-infrastructure-validation-report.md) — inclui a prova real de entrega cross-processo.

## Impacto para releases futuras

- O padrão `RedisSubscriber` + processo worker dedicado (sem HTTP) fica disponível para qualquer Engine futuro que precise consumir eventos cross-processo (Audit Engine é o candidato mais óbvio) — não precisa ser reinventado.
- `dispatchLocally()` em `RedisEventBus` é o método de extensão correto para qualquer worker futuro — documentado no próprio código, não só aqui.
- A correção de `read_write_timeout` deveria ser revisitada quando o Scheduler (componente estrutural sem Release própria) existir — um mecanismo de heartbeat/reconexão mais robusto que "desativar o timeout e torcer" é desejável em produção real, fora do MVP desta Release.
- Nenhuma API HTTP pública para o Memory Engine ainda — quando Mission/Planner (Release 5/6+) precisarem consultar `MemoryRecord`/`DigitalTwin`, essa API nasce então, não antes.
