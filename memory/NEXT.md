# Próximos passos

## Imediato

1. **Aguardar aprovação da Proposal da Release 4B — Memory Infrastructure** ([docs/releases/0004b-memory-infrastructure.md](../docs/releases/0004b-memory-infrastructure.md), revisão 1) — nenhum código de `Application/Infrastructure/Interfaces` do Memory Engine antes disso. Escopo maior que o placeholder original previa: inclui `RedisSubscriber` (primeiro listener Redis cross-processo real do projeto) e `services/memory-worker`, achado ao escrever a Proposal (ver "O que mudou" nela e a entrada em DECISIONS.md desta rodada).
2. Aguardar confirmação para dar push do(s) commit(s) desta rodada (Implementation completa da Release 4A: `packages/memory-engine/`, Decision Log, Validation Report, evento `MemoryReactivated` acrescentado aos catálogos; e a Proposal da Release 4B) — commitado localmente, push ainda não realizado.
3. **Pergunta em aberto, aguardando confirmação do Product Owner**: numeração das Releases 6/7 (Planner/Intent). `ROADMAP.md` mantém `6 — Planner, 7 — Intent` (conforme [ADR-0031](../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md), decisão deliberada de desenvolvimento). A visão de longo prazo mais recente listou `6 — Intent, 7 — Planner` — pode ter sido simplificação de tabela, ou intenção real de reabrir o ADR-0031. Não decidido silenciosamente, ver nota em `ROADMAP.md` e [ADR-0070](../docs/adr/0070-roadmap-estendido-24-releases.md).
4. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
5. Ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
6. Toda decisão relevante de cada rodada precisa terminar registrada no repositório — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).
7. **A partir da Release 4, todo Engine com domínio novo segue o [Processo Oficial de Desenvolvimento de Engines do SIGMA](../docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)** (ADR-0082, documentado em [CONTRIBUTING.md](../CONTRIBUTING.md)) — Research → Manifesto → Model → Lifecycle → Events → Contract → Proposal → Implementation → Validation → Review → Push, nesta ordem, sem pular etapa.

## Perguntas em aberto para a Architecture Review da Release 4B

- `Identifier` (base de Value Object de identificador) segue **duplicada** entre `packages/identity-engine` e `packages/memory-engine` — a Proposal 4A recomendava mover para `packages/core`, mas a aprovação não endereçou a pergunta explicitamente; a Proposal 4B também não a resolve (não é escopo de Infrastructure). Ainda recomendado, ainda não decidido.
- Onde a checagem de staleness de Twin de fato dispara o `warning` no Envelope — responsabilidade de quem consome o Twin ou do próprio Memory Engine — como não há API pública na 4B, isso só passa a importar de fato quando um consumidor real existir (Mission/Planner, Release 6+).
- Três Permissions novas no vocabulário (`memory.promote`/`memory.block_promotion`/`knowledge.curate`, [ADR-0088](../docs/adr/0088-retracao-expiracao-e-governanca-de-promocao.md)) — quando o Identity Engine precisar registrá-las de fato como Roles/Permissions reais, ainda não decidido em que Release isso acontece. A Proposal 4B também não checa Permission nenhuma (sem API pública, sem quem autentique a chamada).
- **Novo, achado ao escrever a Proposal 4B**: `RedisEventBus`/`InMemoryEventBus` nunca implementaram entrega cross-processo real (só local) — sinalizado desde a Release 2/[ADR-0057](../docs/adr/0057-eventbus-composicao-inmemory.md). A Proposal 4B propõe resolver isso com `RedisSubscriber` (`pubSubLoop`) + `services/memory-worker`, o primeiro consumidor cross-processo real do projeto — pendente de aprovação na Architecture Review, não decidido unilateralmente.

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
