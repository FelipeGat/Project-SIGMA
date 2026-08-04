# Próximos passos

## Imediato

1. **Escrever a Proposal completa da Release 3B — Identity Infrastructure** (`docs/releases/0003b-identity-infrastructure.md` é hoje só um placeholder) — persistência real, `IdentityEngineModule implements IModule`, `services/auth`, MariaDB no `docker-compose.yml` com build validado de fato. Precisa de aprovação explícita do Product Owner antes de qualquer código, mesmo processo de sempre.
2. Perguntas em aberto para a Architecture Review de 3B: camada de acesso a dados (PDO puro + runner próprio, recomendado, vs. Doctrine DBAL); onde o filtro por Tenant das `roleAssignments`/`teams` passadas a `Identity::resolveContext()` acontece (Application ou Infrastructure/repositório) — ver nota no Decision Log de 3A; se algum limite de Sessions concorrentes por Identity é necessário (sinalizado, não decidido, em [ADR-0065](../docs/adr/0065-session-autentica-identity.md)).
3. Reconciliar a divergência entre `autonomy_level_required` (inteiro, Sigma Contracts) e `autonomyCapabilities` (mapa nomeado, Identity Engine) — não bloqueia 3B, mas fica para o mais tardar até o Skill Engine (Release 8) definir `Capability.contract.yaml` de verdade.
4. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
5. A partir de agora, ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
6. Toda decisão relevante de cada rodada precisa terminar registrada no repositório (ADR, Decision Log, ou `memory/`) — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).

## Aguardando confirmação do Product Owner (não bloqueia nada agora)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Backlog sinalizado, não implementado

- Endpoint `/health/details` (`status`, `modules[]`, `version`, `uptime`) em `services/gateway`.
- `LogContext` (Workspace/Mission/CorrelationId/Module) como value object para `ILogger`/`Logger`.
- Validação no `LifecycleManager`: um Module que declara `dependsOn()` de outro Module inexistente/não registrado deve ser marcado `FAILED` e bloquear o boot — preparação para um futuro Dependency Graph.
- `IDENTITY_MODEL.md` ainda não foi atualizado para refletir `Identity` como agregado raiz (hoje descreve `Context` como o objeto de mais alto nível) — divergência intencionalmente registrada só via ADR-0064, não editada retroativamente; considerar uma revisão do documento (não uma nova ADR) se isso confundir leitura futura.

Nenhum destes é urgente; ficam aqui para não se perder.

## Depois da Release 3 (3A + 3B)

Release 4 — Memory Engine, mesmo processo de quatro fases. Consome identidade/contexto já resolvido pela Release 3, nunca resolve Tenant/Workspace por conta própria. Avaliar se o mesmo padrão de divisão A/B (Domain-first) se aplica, dado que Memory também modela domínio novo.

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
