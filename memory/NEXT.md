# Próximos passos

## Imediato

1. **Release 3A — Identity Domain está aprovada para implementação** — próximo passo natural é a Architecture Review seguida da Implementation real de `packages/identity-engine/Domain/` (só isso — sem persistência/banco/auth/API, ver [ADR-0060](../docs/adr/0060-release-dividida-em-sub-releases.md)). Nenhum código ainda.
2. Ao implementar 3A, escrever as **cinco ADRs de direção já aprovadas** como parte do trabalho (não depois): Identity como objeto raiz, Session presa a Identity (não a User), Context imutável, Team tipado (System/Business), Autonomy baseada em capability (revisita ADR-0029). Ver `docs/releases/0003a-identity-domain.md`, seção "Direção aprovada".
3. Estruturar `packages/identity-engine/` nas quatro camadas DDD desde o início — só `Domain/` nesta sub-Release ([ADR-0061](../docs/adr/0061-engine-quatro-camadas-ddd.md)).
4. Todo identificador (`TenantId`, `UserId`, `WorkspaceId`, etc.) como Value Object desde a primeira linha de código — nunca `string` primitiva ([ADR-0063](../docs/adr/0063-identificadores-como-value-objects.md)).
5. `Domain/` do Identity Engine nunca importa nada de outro Engine, nem do Kernel — só publica os eventos de [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md) como valor de retorno dos métodos do agregado `Identity` ([ADR-0062](../docs/adr/0062-identity-nunca-conhece-outro-engine.md)).
6. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD, não antes.
7. A partir de agora, ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema — orientação estratégica permanente do Product Owner a partir da Release 3.
8. **Toda decisão relevante de cada rodada precisa terminar registrada no repositório** (ADR, Decision Log, ou `memory/`) antes de considerar a rodada concluída — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md): uma IA sem nenhuma memória externa, só com o repositório clonado, precisa conseguir continuar exatamente daqui.

## Depois que a Release 3A estiver implementada e validada

- Escrever a Proposal completa da **Release 3B — Identity Infrastructure** ([docs/releases/0003b-identity-infrastructure.md](../docs/releases/0003b-identity-infrastructure.md) é hoje só um placeholder) — persistência real, `IdentityEngineModule`, `services/auth`, MariaDB no `docker-compose.yml` com build validado de fato.
- Perguntas em aberto para a Architecture Review de 3B: camada de acesso a dados (PDO puro + runner próprio, recomendado, vs. Doctrine DBAL); um Sigma Contract por entidade de Identity ou só o agregado (`Identity.contract.yaml` já cobre o conceito agregado); nomes de evento ainda não fixados em `contracts/Identity.contract.yaml` (`identity.authenticated`, `identity.workspace_selected` eram candidatos — hoje já formalizados em [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md), conferir se os nomes finais batem).

## Aguardando confirmação do Product Owner (não bloqueia nada agora)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Backlog sinalizado, não implementado

- Endpoint `/health/details` (`status`, `modules[]`, `version`, `uptime`) em `services/gateway`.
- `LogContext` (Workspace/Mission/CorrelationId/Module) como value object para `ILogger`/`Logger`.
- Validação no `LifecycleManager`: um Module que declara `dependsOn()` de outro Module inexistente/não registrado deve ser marcado `FAILED` e bloquear o boot — preparação para um futuro Dependency Graph.

Nenhum destes é urgente; ficam aqui para não se perder, a incorporar quando alguma Release futura tocar o assunto (ou por pedido explícito antes disso).

## Depois da Release 3 (3A + 3B)

Release 4 — Memory Engine, mesmo processo de quatro fases. Consome identidade/contexto já resolvido pela Release 3, nunca resolve Tenant/Workspace por conta própria. Avaliar se o mesmo padrão de divisão A/B (Domain-first) se aplica, dado que Memory também modela domínio novo — decisão a tomar de novo nessa hora, não automática ([ADR-0060](../docs/adr/0060-release-dividida-em-sub-releases.md)).

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
