# Release 2 — SIGMA Bootstrap

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md), para aprovação do Product Owner antes de qualquer linha de código. **Revisão 2** — incorpora as alterações obrigatórias da revisão de CTO: Bootstrap desacoplado de Engines (Module-only), lifecycle estendido, health estilo Kubernetes, System Manifest, Self-Describing Components. A decisão sobre o schema de multiempresa, pendente na revisão 1, está resolvida: vai para a Release 3 — Identity Engine, não para esta Release nem para o Memory Engine (ver [ADR-0039](../adr/0039-identity-engine.md)).

Ver [ADR-0038](../adr/0038-sigma-bootstrap-nao-kernel-completo.md), [ADR-0040](../adr/0040-bootstrap-nao-conhece-engines.md)–[ADR-0046](../adr/0046-self-describing-components.md) e [BOOTSTRAP.md](../../BOOTSTRAP.md)/[SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md) (design de referência que esta proposta implementa).

## Objetivo

Colocar o SIGMA de pé como processo executável, pensando como sistema operacional — não como aplicação: descoberta e registro de Modules a partir de um System Manifest, injeção de dependências, ciclo de vida completo (`discover → register → boot → start → ready → degraded → shutdown`), health-check estilo Kubernetes, e todo componente capaz de se descrever. Nenhuma decisão de domínio (Mission, Intent, Skill, Agent, Identity) é tomada nesta Release. O Bootstrap não sabe que "Engine" existe como conceito — apenas Module.

## Escopo

**Dentro:**
- `packages/core` — primitivas mínimas exigidas pelo bootstrap (identificadores, exceções base).
- `packages/kernel` — Configuration Provider (cada Module declara seu próprio schema de config — [ADR-0044](../adr/0044-configuration-provider.md)), Telemetry completa (Logs/Metrics/Tracing/Audit, não só Logger — [ADR-0043](../adr/0043-telemetry-desde-o-bootstrap.md)), DI Container, contrato `Module` genérico (`name`, `kind`, `dependsOn`, `config()`, `register()`, `boot()`, `describe()`), parser e validação do [System Manifest](../../SYSTEM_MANIFEST.md), Lifecycle estendido, agregação de descriptors Self-Describing.
- `services/gateway` — casca HTTP mínima expondo `GET /health/live`, `GET /health/ready`, `GET /health/startup` (não um único `/health` — [ADR-0042](../adr/0042-health-estilo-kubernetes.md)), cada resposta no [Envelope do SIGMA Protocol](../../SIGMA_PROTOCOL.md#1-o-envelope).
- `services/event-bus` — wrapper mínimo de publish/subscribe sobre Redis (mecanismo técnico; nenhum evento de domínio real ainda).
- `docker/docker-compose.yml` — primeiro ambiente local real (MariaDB + Redis + gateway).
- Um `system-manifest.yaml` de exemplo, versionado no repositório, listando os Modules desta própria Release (só `kernel` e `event-bus`, já que nenhum Engine/Plugin real existe ainda) — prova de que o parser funciona antes de haver algo além de infraestrutura para descrever.

**Fora, explicitamente:**
- Qualquer entidade de domínio (Mission, Intent, Skill, Agent, Capability).
- Identity Engine — Tenant/Company/Workspace/User/Role e Autonomia Progressiva são Release 3, não esta.
- Carregamento de Plugin real (o mecanismo de `Module` é genérico o bastante para suportar isso na Release 8 — ver [BOOTSTRAP.md § Como Plugins são descobertos](../../BOOTSTRAP.md#como-plugins-são-descobertos) — mas nenhum Plugin é carregado nesta Release).
- Chamada a qualquer provedor de IA.
- Qualquer lógica no Kernel que precise saber o `kind` de um Module para decidir o que fazer — se isso aparecer no diff, é um defeito de arquitetura, não um detalhe de implementação ([ADR-0040](../adr/0040-bootstrap-nao-conhece-engines.md)).

## Arquitetura

Segue exatamente o design revisado em [BOOTSTRAP.md](../../BOOTSTRAP.md) e [SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md): `discover` (ler System Manifest, localizar Modules) → `register` (bindings no DI Container, sem executar nada) → `boot` (Configuration Provider e Telemetry primeiro, depois cada Module na ordem topológica de `dependsOn`) → `start` (conexões abertas) → `ready` (Health responde) → `degraded` (estado por Module, não linear) → `shutdown` (ordem inversa). Nenhuma decisão de arquitetura nova nesta proposta — esta seção confirma que a implementação segue os documentos já aprovados.

## Dependências

- Release 1 — SIGMA Protocol, aprovada — o Envelope já é usado por todo endpoint de health desde o primeiro dia.
- MariaDB e Redis disponíveis no ambiente de desenvolvimento — `docker-compose.yml` desta Release resolve isso localmente.

## Riscos

1. **Escopo "infraestrutura pura" pode crescer organicamente** durante a implementação (ex: alguém introduzir um Model de Mission "só para testar" o DI Container, ou um `if (kind === 'plugin')` "só dessa vez"). Mitigado pelos Critérios de Aceite explícitos abaixo.
2. **O contrato `Module` genérico pode não se sustentar** quando Engines reais (Release 4+) tentarem se encaixar nele — descoberto tarde demais seria caro. Mitigado por testar o contrato nesta própria Release com Modules reais, ainda que só de infraestrutura (`kernel`, `event-bus` como Modules de si mesmos).
3. **Self-Describing Components exige disciplina permanente** — um descriptor que mente (declara Capability não implementada, omite dependência real) quebra a confiabilidade do mecanismo inteiro. Sem enforcement automático nesta Release (não há ainda o que validar contra), o risco fica registrado para revisão quando o primeiro Engine real existir.
4. **Testes de uma Release sem domínio nenhum testam comportamento de infraestrutura**, não regra de negócio — mitigado pelos casos de teste explícitos abaixo.

## Entregáveis

- `packages/core` e `packages/kernel` implementados conforme [BOOTSTRAP.md](../../BOOTSTRAP.md).
- `services/gateway` com os três endpoints de health.
- `services/event-bus` com publish/subscribe mínimo, testável.
- `docker/docker-compose.yml` funcional para ambiente local.
- `system-manifest.yaml` de exemplo + parser/validador funcionando.
- **Decision Log desta Release** (`docs/releases/0002-sigma-bootstrap-decision-log.md`), conforme [ADR-0047](../adr/0047-decision-log-por-release.md), escrito ao final da implementação.
- Qualquer ajuste que a implementação revelar necessário ao design é primeiro refletido em `BOOTSTRAP.md`/`SYSTEM_MANIFEST.md`, não silenciosamente divergente deles.

## Testes

- Ordem de boot resolvida corretamente a partir de `dependsOn` declarado por cada Module.
- Dependência circular entre Modules é detectada e falha explicitamente no boot, não em runtime.
- Um Module que falha em `start` impede o sistema de chegar a `ready`.
- Um Module que entra em `degraded` depois de `ready` não derruba os demais — `/health/ready` reflete isso granularmente.
- Shutdown gracioso: ao receber sinal de encerramento, Modules encerram na ordem inversa de dependência.
- `/health/live`, `/health/ready`, `/health/startup` respondem corretamente em cada fase do Lifecycle, cada um no formato do Envelope.
- O parser do System Manifest rejeita um Manifest referenciando um Module ausente, com mensagem explícita — antes do boot completar, não depois.
- `describe()` de cada Module implementado nesta Release retorna um descriptor válido e consistente com o que de fato está registrado.

## Critérios de Aceite

- [ ] Sistema sobe localmente via `docker-compose up`, lendo `system-manifest.yaml`.
- [ ] `/health/live`, `/health/ready`, `/health/startup` respondem no formato do Envelope.
- [ ] Nenhuma entidade de domínio (Mission, Intent, Skill, Agent, Capability, Identity) aparece no diff desta Release.
- [ ] Nenhuma ramificação de código no Kernel depende do `kind` de um Module.
- [ ] Telemetry (não apenas log de texto) ativa desde o primeiro `boot`, correlacionável por `correlationId`/`requestId`.
- [ ] Decision Log desta Release publicado junto com o código.
