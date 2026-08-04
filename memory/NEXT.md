# Próximos passos

## Imediato

1. Aguardar confirmação para dar push do commit final da Release 3B — commitado localmente, push ainda não realizado.
2. **Release 3 (3A + 3B) está completa.** Próximo passo natural: Proposal da **Release 4 — Memory Engine**, mesmo processo de quatro fases. Avaliar se o mesmo padrão de divisão A/B (Domain-first) se aplica — Memory também modela domínio novo ([ADR-0060](../docs/adr/0060-release-dividida-em-sub-releases.md)).
3. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
4. A partir de agora, ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
5. Toda decisão relevante de cada rodada precisa terminar registrada no repositório (ADR, Decision Log, ou `memory/`) — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).

## Padrões descobertos nesta rodada, a levar para Engines futuros com infraestrutura real

- Todo Module lança só `SigmaException`, nunca exceção de infraestrutura crua (`\PDOException`, exceção de cliente HTTP, etc.) — capturar e relançar em `register()`/`boot()`.
- Todo endpoint HTTP captura `\Throwable`, não só as exceções de domínio conhecidas — infraestrutura fora do ar não pode quebrar o contrato do Envelope.
- Um `system-manifest.yaml` compartilhado por múltiplos processos/serviços precisa que cada Module que nem todo processo registra seja `optional: true` — não significa "dispensável", significa "nem todo processo o registra".
- `interface` é palavra reservada do PHP — a camada Interface de ADR-0061 sempre usa a pasta/namespace `Interfaces/` (plural).
- Aggregates com construtor privado + factory que dispara evento (`create()`, `assign()`, `start()`) precisam de um `reconstitute()` companheiro para hidratar do banco sem redisparar o evento.

## Aguardando confirmação do Product Owner (não bloqueia nada agora)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Backlog sinalizado, não implementado

- Endpoint `/health/details` (`status`, `modules[]`, `version`, `uptime`) em `services/gateway`.
- `LogContext` (Workspace/Mission/CorrelationId/Module) como value object para `ILogger`/`Logger`.
- Validação no `LifecycleManager`: um Module que declara `dependsOn()` de outro Module inexistente/não registrado deve ser marcado `FAILED` e bloquear o boot.
- Nenhum limite de Sessions concorrentes por Identity (ADR-0065).
- Divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) — reconciliar até o Skill Engine (Release 8), ver ADR-0068.
- `PermissionId` sem uso na Infrastructure — avaliar se `Permission` precisa de um catálogo com metadados algum dia.

Nenhum destes é urgente; ficam aqui para não se perder.

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
