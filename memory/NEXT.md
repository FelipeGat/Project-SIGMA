# Próximos passos

## Imediato

1. **Release 5 — Mission Engine: COMPLETA (5A + 5B + 5C).** `packages/mission-engine/src/` tem as quatro camadas DDD completas — `MissionRepository`/`PdoMissionRepository` (seis tabelas, `subtask_retry_attempts` foi achado real), quinze casos de uso, `MissionEngineModule`. **Sem worker/listener Redis** — diferente da 4B, nenhum Engine anterior publica evento que o Mission Engine precise consumir ainda. 51 testes no pacote, 246 no monorepo, **0 pulados** (primeira vez — MariaDB real de pé). Ver [Decision Log 5C](../docs/releases/0005c-mission-infrastructure-decision-log.md)/[Validation Report 5C](../docs/releases/0005c-mission-infrastructure-validation-report.md).
2. **Próxima decisão real do Product Owner**: o que vem depois da Release 5 — seguir para o Planner Engine (Release 6, próximo da fila do ROADMAP.md) ou primeiro endereçar o achado de alta prioridade da 4.5 (`restart policy` ausente no `docker-compose.yml`, item 3 abaixo). Mission Engine ainda não tem nenhum service registrando seu Module (sem worker/API HTTP) — decisão de quando isso passa a fazer sentido também em aberto, provavelmente natural quando o Planner Engine existir.
3. **Achado de alta prioridade da Release 4.5, ainda não endereçado**: nenhum serviço do `docker-compose.yml` tem `restart policy` — `memory-worker` não recupera sozinho de nenhuma interrupção de conexão Redis. Recomendado, não implementado: `restart: on-failure` (baixo risco) + considerar Redis Streams (mudança maior).
4. Aguardar confirmação para dar push do(s) commit(s) pendentes (Implementation completa de 4A/4B, ROADMAP.md, Implementation/Validation da 4.5, Release 5A/5B/5C completas) — commitado localmente, push ainda não realizado.
5. **`docker/docker-compose.yml` — serviço `mariadb` está de pé** (subido nesta sessão para a validação da 5C, porta `13306`), com os três bancos de teste (`sigma_identity_test`/`sigma_memory_test`/`sigma_mission_test`) já criados — útil para a próxima rodada de validação não precisar repetir esse setup, se a sessão continuar com o mesmo Docker Desktop ativo.
5. **Pergunta de numeração Planner/Intent (6/7) ENCERRADA em 2026-08-04** — Product Owner confirmou explicitamente `6 — Planner, 7 — Intent`, mantendo [ADR-0031](../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md) sem alteração. Não é mais pendência.
6. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
7. Ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
8. Toda decisão relevante de cada rodada precisa terminar registrada no repositório — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).
9. **A partir da Release 4, todo Engine com domínio novo segue o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)** (ADR-0082, documentado em [CONTRIBUTING.md](../CONTRIBUTING.md)) — Research → Manifesto → Model → Lifecycle → Events → Contract → Proposal → Implementation → Validation → Review → Push, nesta ordem, sem pular etapa.

## Perguntas resolvidas durante a Implementation da Release 5B (ver Decision Log)

- "A Subtask já produziu efeito colateral ou não" — resolvido como parâmetro explícito (`bool $hasProducedEffect`) passado por quem chama `failSubtask()`/`failValidation()`, o próprio `Domain/` não infere isso sozinho (permanece decisão de Application/quem chama, como o modelo já previa).
- `Mission::advanceToNextSubtask()` (não `addSubtask()`/`evaluateApproval()` separados) e `passValidation()`/`failValidation()` (não um único `completeValidation()`) — ver Decision Log para o porquê.
- `Subtask::compensate()` aceita origem `Failed` **ou** `Validated` — achado de modelagem ao implementar `failValidation()`.
- Se a Release 5B se divide em sub-Releases Domain/Infrastructure (ADR-0060) — **decidido: não**, 5B entrega só `Domain/`, mesmo escopo que 4A cobriu para Memory. `Application`/`Infrastructure`/`Interfaces` viraram a Release 5C (nomeada depois, não uma quarta sub-Release).
- `subtask_retry_attempts` como sexta tabela, `Subtask`/`ApprovalGate` ganhando `reconstitute()` próprio, `compensations`/`mission_history` substituídas por completo (não upsertadas) — todos achados/decisões da Implementation da 5C, ver [Decision Log 5C](../docs/releases/0005c-mission-infrastructure-decision-log.md).

## Perguntas ainda em aberto

- Número exato de tentativas de retry e política de backoff — parâmetro de configuração, decisão de Application, não decidido ainda (a Application da 5C não impõe limite — quem chama decide quando parar de tentar).
- Timeout de um `ApprovalGate` pendente — expira sozinho ou fica pendente indefinidamente?
- `packages/README.md` ainda tem outras dependências não revisadas nesta rodada (ex: `agent-engine`/`execution-engine` → `mission-engine` — essas fazem sentido pela Ordem de Desenvolvimento, mas não foram auditadas em detalhe).
- Quando o Mission Engine ganha um consumidor real (worker/API HTTP) — provavelmente natural quando o Planner Engine (Release 6) existir e publicar `MissionPlanned`, não decidido.

## Pendências reais deixadas pela Release 4 (4A + 4B) e 4.5, para quando fizerem falta

- **PRIORIDADE ALTA — `docker-compose.yml` sem `restart policy` em nenhum serviço** (achado da 4.5, não previsto) — `memory-worker` fica fora do ar até um restart manual mesmo após uma interrupção momentânea do Redis. Correção recomendada: `restart: on-failure` em todos os serviços, mudança pequena e de baixo risco.
- **Perda de mensagem durante queda do `memory-worker` é definitiva, sem fila/replay** (confirmado com evidência real na 4.5) — Redis pub/sub não persiste para assinante ausente. Migrar para Redis Streams resolveria; não decidido em que Release.
- `Identifier` (base de Value Object de identificador) segue **duplicada** em três pacotes (`packages/identity-engine`, `packages/memory-engine`, `packages/mission-engine`) — consolidação em `packages/core` ainda recomendada, ainda não decidida.
- Onde a checagem de staleness de Twin de fato dispara o `warning` no Envelope — só passa a importar quando um consumidor real existir (Mission/Planner, Release 6+).
- Três Permissions no vocabulário (`memory.promote`/`memory.block_promotion`/`knowledge.curate`, [ADR-0088](../docs/adr/0088-retracao-expiracao-e-governanca-de-promocao.md)) — sem checagem real ainda, sem API pública que precise delas.
- **`KnowledgeFolderIndexer` implementado (testado, inclusive contra MariaDB real) mas sem gatilho de execução em produção** — nada chama automaticamente; decidir quando (comando CLI, cron via Scheduler futuro) fica para quando houver necessidade real.
- **`handleWorkspaceSelected()` ignora silenciosamente entrega fora de ordem** (`workspace.selected` antes de `identity.created` ter sido processado) — sem retry/fila nesta Release.
- Throughput de processamento do `memory-worker` (~112 eventos/s, medido na 4.5) é single-threaded por natureza — paralelização (múltiplas instâncias, ou consumer groups do Streams) é decisão futura se o volume da Mission Engine exigir mais.
- `RedisSubscriber`/`services/memory-worker` (o padrão de worker cross-processo sem HTTP) ficam disponíveis para qualquer Engine futuro que precise consumir eventos entre processos — Audit Engine é o candidato mais óbvio.

## Cinco componentes estruturais sinalizados pelo Product Owner, sem Release própria ainda

Ver a tabela completa em `ROADMAP.md`. Scheduler, Secrets Manager, Cache Layer, Observability, Policy Engine — cada um provavelmente se encaixa numa Release já numerada (11/14/18/23), a confirmar quando a Release correspondente for desenhada em detalhe.

## Direções aprovadas para o Identity Engine, não implementadas (aguardando a próxima mudança de comportamento real)

- [ADR-0075](../docs/adr/0075-workspace-context-pertencem-a-session.md) — `Workspace`/`Context` migrarem conceitualmente de `Identity` para `Session`.
- [ADR-0076](../docs/adr/0076-metadata-padrao-em-eventos-de-dominio.md) — eventos de domínio ganharem metadata padrão (`id`/`timestamp`/`correlationId`/`causationId`/`actor`/`workspace`) além do `payload`.
- Nenhum limite de Sessions concorrentes por Identity — sinalizado em [ADR-0065](../docs/adr/0065-session-autentica-identity.md).
- Divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) — reconciliar até o Skill Engine (Release 8), ver [ADR-0068](../docs/adr/0068-autonomy-por-capability.md).

## Achado técnico a levar para o próximo Engine com persistência

Migrations do Identity Engine só rodam na primeira requisição HTTP (dentro de `IdentityEngineModule::boot()`), não há hook de inicialização de container — descoberto testando em ambiente Docker genuinamente limpo. Inofensivo na prática para um serviço HTTP, mas vale reavaliar explicitamente na Architecture Review da Release 4B, já que Memory Engine também terá persistência própria.

## Backlog sinalizado, não implementado

- Endpoint `/health/details` (`status`, `modules[]`, `version`, `uptime`) em `services/gateway`.
- `LogContext` (Workspace/Mission/CorrelationId/Module) como value object para `ILogger`/`Logger`.
- Validação no `LifecycleManager`: Module com dependência declarada mas inexistente deve ser marcado `FAILED`.
- Endpoint HTTP para `RegisterIdentity` (caso de uso já existe, nunca foi exposto em `services/auth` — não pedido em nenhuma Proposal até agora).
- `PermissionId` sem uso na Infrastructure.
- `IDENTITY_MODEL.md` desatualizado quanto a `Context`/`Identity` (só corrigido via ADR-0064, não no próprio texto).

Nenhum destes é urgente; ficam aqui para não se perder.

## Aguardando confirmação do Product Owner (não bloqueia nada agora)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
