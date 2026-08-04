# Próximos passos

## Imediato

1. **Aguardar aprovação do Product Owner para [IDENTITY_MODEL.md](../IDENTITY_MODEL.md)** — bloqueante, sem exceção, para qualquer código do Identity Engine.
2. **Aguardar aprovação da Proposal da Release 3** ([docs/releases/0003-identity-engine.md](../docs/releases/0003-identity-engine.md), revisão 1) — pode passar por mais revisões, mesmo padrão da Release 2 (3 revisões antes de aprovada).
3. Aguardar confirmação para dar push do commit com os refinamentos pré-Release-3 (EventBus por composição, `manifestVersion`, ADRs 0056-0058, `VALIDATION_REPORT.md`, `IDENTITY_MODEL.md`, Proposal da Release 3) — commitado localmente, push **não realizado** ainda.
4. Validar `docker-compose up --build` num ambiente com Docker Desktop ativo — pendência repetida desde a Release 2, idealmente resolvida antes ou durante a Release 3 (que já introduz MariaDB no compose).
5. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD, não antes.

## Perguntas em aberto na Proposal da Release 3 (para a Architecture Review)

- Camada de acesso a dados em `packages/identity-engine`: PDO puro + runner de migration próprio (recomendado, mantém o precedente framework-agnóstico) vs. Doctrine DBAL. Sinalizado na Proposal, não decidido.
- Um Sigma Contract por entidade de Identity, ou um só para o Engine inteiro — a definir.

## Aguardando confirmação do Product Owner (não bloqueia a Release 2, nem a Release 3 em si)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Backlog sinalizado, não implementado (observações que não entraram nas "quatro entregas" pré-Release-3)

- Endpoint `/health/details` (`status`, `modules[]`, `version`, `uptime`) em `services/gateway`.
- `LogContext` (Workspace/Mission/CorrelationId/Module) como value object para `ILogger`/`Logger`.
- Validação no `LifecycleManager`: um Module que declara `dependsOn()` de outro Module inexistente/não registrado deve ser marcado `FAILED` e bloquear o boot (hoje a dependência ausente do conjunto carregado é silenciosamente ignorada) — preparação para um futuro Dependency Graph.

Nenhum destes é urgente; ficam aqui para não se perder, a incorporar quando alguma Release futura tocar o assunto (ou por pedido explícito antes disso).

## Depois da Release 3

Release 4 — Memory Engine, mesmo processo de quatro fases. Consome identidade/contexto já resolvido pela Release 3, nunca resolve Tenant/Workspace por conta própria.

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
