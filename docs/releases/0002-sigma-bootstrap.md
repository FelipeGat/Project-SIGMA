# Release 2 — SIGMA Bootstrap

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), para aprovação do Product Owner antes de qualquer linha de código. Ver [ADR-0038](../adr/0038-sigma-bootstrap-nao-kernel-completo.md) (por que esta Release não é mais chamada "Kernel") e [BOOTSTRAP.md](../../BOOTSTRAP.md) (o design de referência que esta proposta implementa).

## Objetivo

Colocar o SIGMA de pé como processo executável: inicialização determinística, injeção de dependências, ciclo de vida (`boot → start → ready → shutdown`), health-check. Nenhuma decisão de domínio (Mission, Intent, Skill, Agent) é tomada nesta Release — é a fundação sobre a qual toda Release seguinte constrói, testável isoladamente antes de qualquer Engine existir.

## Escopo

**Dentro:**
- `packages/core` — primitivas mínimas exigidas pelo bootstrap (identificadores, exceções base).
- `packages/kernel` — Config (carregamento por ambiente, falha explícita se faltar valor obrigatório), Logger estruturado, DI Container, registro de `Module` com resolução topológica de `dependsOn` (incluindo detecção de dependência circular), Lifecycle (`boot`/`start`/`ready`/`shutdown`), Health.
- `services/gateway` — casca HTTP mínima expondo **apenas** `GET /health`, respondendo no [Envelope do SIGMA Protocol](../../SIGMA_PROTOCOL.md#1-o-envelope) (`protocolVersion`, `correlationId`, `requestId`, `timestamp` reais; `data: { status: "ready" }` quando aplicável).
- `services/event-bus` — wrapper mínimo de publish/subscribe sobre Redis (mecanismo técnico; nenhum evento de domínio real ainda).
- `docker/docker-compose.yml` — primeiro ambiente local real (MariaDB + Redis + gateway), justificando a pasta `docker/` deixar de estar vazia (ver nota em [docker/README.md](../../docker/README.md)).

**Fora, explicitamente:**
- Qualquer entidade de domínio (Mission, Intent, Skill, Agent, Capability).
- Carregamento de Plugin real (o mecanismo de `Modules` é genérico o bastante para suportar isso depois — ver [BOOTSTRAP.md § Como Plugins são descobertos](../../BOOTSTRAP.md#como-plugins-são-descobertos) — mas nenhum Plugin é carregado nesta Release).
- Chamada a qualquer provedor de IA.
- Schema de multiempresa (Tenant/Company/Workspace/User/Role) — ver **Riscos**, decisão pendente.

## Arquitetura

Segue exatamente o design já revisado em [BOOTSTRAP.md](../../BOOTSTRAP.md): Config → Logger → DI Container → registro de Modules → `boot()` de cada Module na ordem topológica → Health disponível quando todos os Modules obrigatórios reportam `ready`. Nenhuma decisão de arquitetura nova nesta proposta — esta seção existe para confirmar que a implementação segue o documento já aprovado, não para reabri-lo.

## Dependências

- Release 1 — SIGMA Protocol, aprovada — o endpoint de health já responde no Envelope desde o primeiro dia.
- MariaDB e Redis disponíveis no ambiente de desenvolvimento — primeira vez que isso é necessário no projeto; `docker-compose.yml` desta Release resolve isso localmente.

## Riscos

1. **Onde vive o schema de multiempresa?** Estava bundlado na antiga Release 2 ("Kernel"); a Release 2 (Bootstrap) não o inclui mais. Proposto para a Release 3 (Memory Engine) — **esta decisão precisa ser confirmada antes da aprovação desta proposta**, não durante a implementação, para não haver ambiguidade sobre em qual Release o `tenant_id` obrigatório (ver [ADR-0021](../adr/0021-multitenancy-desde-o-schema.md)) é de fato introduzido.
2. **Escopo "infraestrutura pura" pode crescer organicamente** durante a implementação (ex: alguém introduzir um Model de Mission "só para testar" o DI Container). Mitigado pelo Critério de Aceite explícito abaixo — nenhuma entidade de domínio no diff desta Release.
3. **Testes de uma Release sem domínio nenhum testam comportamento de infraestrutura**, não regra de negócio — risco de testes fracos ou genéricos se não houver disciplina; mitigado pelos casos de teste explícitos abaixo.

## Entregáveis

- `packages/core` e `packages/kernel` implementados conforme [BOOTSTRAP.md](../../BOOTSTRAP.md).
- `services/gateway` com `GET /health` respondendo no Envelope.
- `services/event-bus` com publish/subscribe mínimo, testável.
- `docker/docker-compose.yml` funcional para ambiente local.
- Qualquer ajuste que a implementação revelar necessário ao design é primeiro refletido em `BOOTSTRAP.md`, não silenciosamente divergente dele.

## Testes

- Ordem de boot resolvida corretamente a partir de `dependsOn` declarado por cada Module.
- Dependência circular entre Modules é detectada e falha explicitamente no boot, não em runtime.
- Um Module que falha em `start` impede o sistema de chegar a `ready`.
- Shutdown gracioso: ao receber sinal de encerramento, Modules encerram na ordem inversa de dependência.
- `GET /health` retorna `200` somente quando todos os Modules obrigatórios reportam `ready`, e o corpo da resposta é um Envelope válido (`protocolVersion`, `correlationId`, `requestId`, `timestamp` presentes e corretos).

## Critérios de Aceite

- [ ] Sistema sobe localmente via `docker-compose up`.
- [ ] `GET /health` responde no formato do Envelope, com `success: true` e `data.status: "ready"` quando tudo está inicializado.
- [ ] Nenhuma entidade de domínio (Mission, Intent, Skill, Agent, Capability) aparece no diff desta Release.
- [ ] A decisão sobre onde vive o schema de multiempresa está tomada — não pendente — antes do merge.
- [ ] Logs estruturados desde o primeiro `boot`, correlacionáveis (ver [TELEMETRY.md](../../TELEMETRY.md)).
