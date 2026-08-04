# Próximos passos

## Imediato

1. Aguardar confirmação para dar push do commit da Release 3.5 — commitado localmente, push ainda não realizado.
2. **Próximo passo natural: Proposal da Release 4 — Memory Engine.** Reconhecida pelo Product Owner como o segundo marco mais importante do projeto, atrás só da Foundation — "praticamente todo Engine seguinte depende da qualidade do que ela expuser". Investir tempo extra na modelagem, mesmo que atrase o cronograma (recomendação explícita do Product Owner).
3. **Pergunta em aberto, aguardando confirmação do Product Owner**: numeração das Releases 6/7 (Planner/Intent). `ROADMAP.md` mantém `6 — Planner, 7 — Intent` (conforme [ADR-0031](../docs/adr/0031-ordem-runtime-vs-desenvolvimento.md), decisão deliberada de desenvolvimento). A visão de longo prazo mais recente listou `6 — Intent, 7 — Planner` — pode ter sido simplificação de tabela, ou intenção real de reabrir o ADR-0031. Não decidido silenciosamente, ver nota em `ROADMAP.md` e [ADR-0070](../docs/adr/0070-roadmap-estendido-24-releases.md).
4. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
5. Ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
6. Toda decisão relevante de cada rodada precisa terminar registrada no repositório — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).

## Cinco componentes estruturais sinalizados pelo Product Owner, sem Release própria ainda

Ver a tabela completa em `ROADMAP.md`. Scheduler, Secrets Manager, Cache Layer, Observability, Policy Engine — cada um provavelmente se encaixa numa Release já numerada (11/14/18/23), a confirmar quando a Release correspondente for desenhada em detalhe.

## Direções aprovadas para o Identity Engine, não implementadas (aguardando a próxima mudança de comportamento real)

- [ADR-0075](../docs/adr/0075-workspace-context-pertencem-a-session.md) — `Workspace`/`Context` migrarem conceitualmente de `Identity` para `Session`.
- [ADR-0076](../docs/adr/0076-metadata-padrao-em-eventos-de-dominio.md) — eventos de domínio ganharem metadata padrão (`id`/`timestamp`/`correlationId`/`causationId`/`actor`/`workspace`) além do `payload`.
- Nenhum limite de Sessions concorrentes por Identity — sinalizado em [ADR-0065](../docs/adr/0065-session-autentica-identity.md).
- Divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) — reconciliar até o Skill Engine (Release 8), ver [ADR-0068](../docs/adr/0068-autonomy-por-capability.md).

## Achado técnico a levar para o próximo Engine com persistência

Migrations do Identity Engine só rodam na primeira requisição HTTP (dentro de `IdentityEngineModule::boot()`), não há hook de inicialização de container — descoberto testando em ambiente Docker genuinamente limpo. Inofensivo na prática para um serviço HTTP, mas vale reavaliar explicitamente na Architecture Review da Release 4 se Memory Engine também tiver persistência própria.

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
