# Próximos passos

## Imediato

1. **Aguardar aprovação da Proposal da Release 3B — Identity Infrastructure** ([docs/releases/0003b-identity-infrastructure.md](../docs/releases/0003b-identity-infrastructure.md), revisão 1) — pode passar por mais revisões, mesmo padrão da Release 2/3A. Nenhum código antes disso.
2. Aguardar confirmação para dar push do commit `4b31a6d` (Release 3A implementada) — commitado localmente, push ainda não realizado.
3. Decisões já tomadas na Proposal de 3B, não mais em aberto: camada de acesso a dados = PDO puro + runner de migration próprio (sem Doctrine DBAL); filtro por Tenant vive dentro de cada repositório (`WHERE tenant_id = ?` obrigatório, nunca opcional), nunca é responsabilidade de quem chama.
4. Ainda em aberto para a Architecture Review de 3B: se algum limite de Sessions concorrentes por Identity é necessário (sinalizado, não decidido, em [ADR-0065](../docs/adr/0065-session-autentica-identity.md)); se migrations rodando dentro de `boot()` do `IModule` se sustenta bem no contrato genérico (risco sinalizado desde 3A).
5. Reconciliar a divergência entre `autonomy_level_required` (inteiro, Sigma Contracts) e `autonomyCapabilities` (mapa nomeado, Identity Engine) — não bloqueia 3B, fica para o Skill Engine (Release 8).
6. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD.
7. A partir de agora, ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema.
8. Toda decisão relevante de cada rodada precisa terminar registrada no repositório (ADR, Decision Log, ou `memory/`) — teste do [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md).

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
