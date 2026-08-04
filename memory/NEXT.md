# Próximos passos

## Imediato

1. **Release 3 está aprovada para implementação** — próximo passo natural é a Architecture Review seguida da Implementation real (schema/migrations, `IdentityEngineModule`, `services/auth`). Nenhum código ainda.
2. Ao implementar, escrever as **cinco ADRs de direção já aprovadas** como parte do trabalho (não depois): Identity como objeto raiz, Session presa a Identity (não a User), Context imutável, Team tipado (System/Business), Autonomy baseada em capability (revisita ADR-0029). Ver `docs/releases/0003-identity-engine.md`, seção "Direção aprovada".
3. Validar `docker-compose up --build` com MariaDB de fato — agora é Critério de Aceite explícito da Release 3, não pode repetir a pendência da Release 2.
4. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) — decisão explícita de adiar para a Release de CI/CD, não antes.
5. A partir de agora, ao propor soluções de arquitetura no SIGMA: perguntar "como o SIGMA deve fazer" antes de olhar para como um framework conhecido resolve o mesmo problema — orientação estratégica permanente do Product Owner a partir da Release 3.

## Perguntas em aberto na Proposal da Release 3 (para a Architecture Review)

- Camada de acesso a dados em `packages/identity-engine`: PDO puro + runner de migration próprio (recomendado, mantém o precedente framework-agnóstico) vs. Doctrine DBAL. Sinalizado na Proposal, não decidido.
- Um Sigma Contract por entidade de Identity, ou um só para o Engine inteiro — `Identity.contract.yaml` já cobre o conceito agregado; entidades internas (Tenant/Role/Permission/etc.) podem não precisar de contrato próprio, a confirmar durante a Implementation.
- Nomes de evento de domínio do Identity Engine (`identity.authenticated`, `identity.workspace_selected`, etc.) — listados como candidatos em `contracts/Identity.contract.yaml`, não fixados ainda.

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
