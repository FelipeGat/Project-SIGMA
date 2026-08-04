# Release 2 — SIGMA Bootstrap

Proposta formal, no formato exigido por [ADR-0010](../adr/0010-processo-por-epicos-com-aprovacao.md). **Revisão 3 — aprovada, implementação autorizada.** Segue o processo de quatro fases oficializado em [ADR-0048](../adr/0048-processo-quatro-fases.md): esta proposta já passou por duas rodadas de Architecture Review; a partir daqui, Implementation executa exatamente o que está descrito abaixo — nada além.

Ver [ADR-0038](../adr/0038-sigma-bootstrap-nao-kernel-completo.md), [ADR-0040](../adr/0040-bootstrap-nao-conhece-engines.md)–[ADR-0046](../adr/0046-self-describing-components.md), [ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md), [ADR-0053](../adr/0053-escopo-restrito-release-2.md) e [BOOTSTRAP.md](../../BOOTSTRAP.md)/[SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md) (design de referência que esta proposta implementa).

## Objetivo

Colocar o SIGMA de pé como processo executável, pensando como sistema operacional: descoberta e registro de Modules a partir de um System Manifest, injeção de dependências exclusivamente por interface, ciclo de vida completo, health-check estilo Kubernetes, todo componente capaz de se descrever. Nenhuma decisão de domínio é tomada nesta Release.

## Escopo

**Existe:**
- Bootstrap (`discover → register → boot → start → ready → degraded → shutdown`)
- Container de DI (`IContainer`)
- Module Loader (contrato `IModule`, resolução topológica de `dependsOn`)
- Configuration Provider (`IConfiguration`)
- Lifecycle Manager
- Health Manager (`IHealth`, endpoints `/health/live`, `/health/ready`, `/health/startup`)
- Event Bus — infraestrutura apenas (`IEventBus`, publish/subscribe sobre Redis, sem eventos de domínio)
- Logger/Telemetry (`ILogger`, os quatro pilares — ver [TELEMETRY.md](../../TELEMETRY.md))
- System Manifest Loader (parser e validação de `system-manifest.yaml`)

**Não existe ainda:** IA, Missions, Planner, Intent, Memory, Identity, Plugins de negócio, Skills, Agents, **banco de dados**, autenticação, interface Web. Ver [ADR-0053](../adr/0053-escopo-restrito-release-2.md) — lista explícita, não interpretável.

**Onde vive:**
- `packages/core` — primitivas mínimas (identificadores, exceções base).
- `packages/kernel` — as seis interfaces (`ILogger`, `IEventBus`, `IModule`, `IConfiguration`, `IHealth`, `IContainer`), suas implementações concretas, Lifecycle Manager, System Manifest Loader.
- `services/gateway` — casca HTTP mínima expondo os três endpoints de health, cada resposta no [Envelope](../../SIGMA_PROTOCOL.md#1-o-envelope).
- `services/event-bus` — wrapper de publish/subscribe sobre Redis.
- `docker/docker-compose.yml` — Redis + gateway. **Sem MariaDB** (nada a persistir ainda).
- `system-manifest.yaml` de exemplo, listando apenas os Modules desta Release (`kernel`, `event-bus`).

## Arquitetura

Segue [BOOTSTRAP.md](../../BOOTSTRAP.md) e [SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md): `discover` (ler Manifest) → `register` (bindings via interface, nunca classe concreta — [ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md)) → `boot` (Configuration Provider e Telemetry primeiro, depois cada Module em ordem topológica) → `start` (Redis conectado) → `ready` → `degraded` (por Module) → `shutdown` (ordem inversa).

## Dependências

- Release 1 — SIGMA Protocol, aprovada.
- Redis disponível no ambiente de desenvolvimento (`docker-compose.yml` resolve isso localmente).

## Riscos

1. **O contrato `IModule`/interfaces pode não se sustentar** quando Engines reais (Release 4+) tentarem se encaixar — mitigado por testar o contrato nesta Release com Modules reais de infraestrutura (`kernel`, `event-bus`).
2. **Self-Describing Components exige disciplina permanente** — sem enforcement automático ainda (nada para validar contra), risco registrado para quando o primeiro Engine real existir.
3. **Escopo pode crescer organicamente** — mitigado pelos Critérios de Aceite explícitos e pela lista fechada de [ADR-0053](../adr/0053-escopo-restrito-release-2.md).

## Entregáveis

- `packages/core`, `packages/kernel` implementados.
- `services/gateway` com os três endpoints de health.
- `services/event-bus` com publish/subscribe mínimo.
- `docker/docker-compose.yml` (Redis + gateway).
- `system-manifest.yaml` de exemplo + parser/validador.
- Contrato `contracts/Module.contract.yaml` (primeiro Contract real do projeto — ver [ADR-0049](../adr/0049-sigma-contracts.md)).
- Primeira linha de [COMPATIBILITY.md](../../COMPATIBILITY.md): Kernel 1.0 / Protocol 1.0 / Plugin API 1.0 ✅.
- **Decision Log** (`docs/releases/0002-sigma-bootstrap-decision-log.md`).

## Testes — três níveis ([ADR-0054](../adr/0054-tres-niveis-de-validacao.md))

### 1. Testes Automatizados
- Ordem de boot resolvida corretamente a partir de `dependsOn`; dependência circular detectada e rejeitada no boot.
- Um Module que falha em `start` impede `ready`.
- Shutdown gracioso em ordem inversa.
- `describe()` de cada Module retorna um descriptor válido.

### 2. Architecture Validation
- Nenhum Module importa uma classe concreta do Kernel — apenas as seis interfaces.
- Nenhuma ramificação de código no Kernel depende do `kind` de um Module.
- Implementação respeita `contracts/Module.contract.yaml` e o Envelope do SIGMA Protocol.

### 3. Scenario Validation
- Sistema sobe sem módulos opcionais.
- Sistema sobe com um módulo opcional ausente.
- Um módulo entra em `degraded` — sistema continua operando, `/health/ready` reflete isso granularmente.
- O System Manifest possui erro — boot recusado, mensagem explícita.
- Um módulo com versão incompatível (ver [COMPATIBILITY.md](../../COMPATIBILITY.md)) é rejeitado antes de `ready`.
- `/health/live`, `/health/ready`, `/health/startup` respondem corretamente em cada fase do Lifecycle.

## Critérios de Aceite

- [ ] Sistema sobe localmente via `docker-compose up`, lendo `system-manifest.yaml`.
- [ ] Os três endpoints de health respondem no formato do Envelope.
- [ ] Nenhuma entidade de domínio (Mission, Intent, Skill, Agent, Capability, Identity) e nenhum banco de dados aparecem no diff.
- [ ] Todo acesso a capacidade do Kernel passa por uma das seis interfaces — nunca uma classe concreta.
- [ ] `contracts/Module.contract.yaml` e a primeira linha de `COMPATIBILITY.md` publicados.
- [ ] Os três níveis de validação (Testes Automatizados, Architecture Validation, Scenario Validation) executados e documentados.
- [ ] Decision Log publicado junto com o código.
