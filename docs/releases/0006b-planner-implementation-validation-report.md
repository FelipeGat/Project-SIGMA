# Release 6B — Planner Implementation — Validation Report

Prova de execução da fase de Validation (ver [ADR-0048](../adr/0048-processo-quatro-fases.md) e [ADR-0056](../adr/0056-validation-report-obrigatorio.md)). Complementar ao [Decision Log](0006b-planner-implementation-decision-log.md).

## Release

[Release 6B — Planner Implementation (ciclo fechado)](0006b-planner-implementation.md), revisão 2, implementando a modelagem da [Release 6A](0006a-planner-research.md) e as ADRs [0095](../adr/0095-planner-sem-estado.md)/[0096](../adr/0096-plan-duplicado-por-bounded-context.md)/[0097](../adr/0097-planner-falha-visivelmente.md). Aprovada pelo Product Owner ("aprovado, pode implementar").

## Ambiente

- macOS (Darwin 25.5.0), arm64. Docker Desktop.
- `docker compose up -d --build` executado de fato nesta rodada, com os seis serviços.
- Data da execução: 2026-08-11.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Suíte automatizada (host): `8.5.8`. Containers: `8.2.33`.
- **Gap sinalizado, não omitido** — mesma pendência das Releases anteriores.

## Docker

Seis serviços de pé. Novidades desta Release:

- `docker/mission-worker.Dockerfile` (novo) — **não** copia `packages/planner-engine`: o acoplamento é o payload do evento, nunca código.
- `mission-worker` no `docker-compose.yml`, com `restart: on-failure` e **sem** `healthcheck` (exceção de [ADR-0094](../adr/0094-health-endpoints-pertencem-ao-kernel.md) — não escuta HTTP).
- `docker/gateway.Dockerfile` passou a copiar `packages/planner-engine`.

```
SERVICE          STATUS
auth             Up 2 minutes (healthy)
gateway          Up 2 minutes (healthy)
mariadb          Up About an hour (healthy)
memory-worker    Up 2 minutes
mission-worker   Up 23 seconds
redis            Up 3 hours (healthy)
```

`GET /health/ready` do gateway reporta os **cinco** Modules, todos `ready`: `kernel`, `event-bus`, `identity-engine`, `mission-engine`, `planner-engine`.

Log do worker na subida: `mission-worker: pronto, aguardando mission.planned.`

## HTTP e o ciclo fechado

### O ciclo completo — Critério de Aceite 3

| # | Passo | Resultado real |
|---|---|---|
| 1 | `bootstrap-identity.php` | exit `0` |
| 2 | `POST /auth/login` → `POST /auth/workspace` | `200` / `200` |
| 3 | `POST /intents` (`kind: nova_implantacao`) | **`202`** |
| 4 | `mission-worker` consome | log: `Mission a6a66196-… criada a partir de mission.planned (correlationId c6a8b5a0-…)` |
| 5 | Mission no MariaDB | `status=created`, `correlation_id=c6a8b5a0-…` |
| 6 | `GET /missions/{id}` | **`200`**, `plan.source: planner` |

Resposta real do passo 3:

```json
{
  "intentId": "fb6742fe-e2bb-4699-aeda-de762626dc77",
  "correlationId": "c6a8b5a0-6422-4085-b7dc-b0d04f049a00",
  "kind": "nova_implantacao",
  "status": "planned",
  "subtaskCandidates": 4,
  "note": "Plan publicado. A Mission é criada de forma assíncrona — ..."
}
```

E os quatro passos que **o SIGMA decidiu sozinho**, lidos de volta no passo 6:

```
nivel 1 | Confirmar escopo contratado a partir do orçamento de origem
nivel 3 | Provisionar/configurar o ambiente do cliente no sistema contratado
nivel 2 | Agendar treinamento/onboarding com o cliente
nivel 2 | Acompanhar até o critério de "implantado" e a transição para operação/suporte contínuo
```

São as quatro "Fases esperadas" de `playbooks/nova-implantacao.md`, com o nível 3 exatamente no passo que aquele Playbook marca como ponto de decisão humana. **Ninguém escreveu esses passos na requisição.**

### Critério 4 — o `correlationId` atravessa a fronteira assíncrona

`c6a8b5a0-6422-4085-b7dc-b0d04f049a00` aparece, sem mudar, na resposta `202`, no log do worker e na coluna `correlation_id` da Mission persistida. É a primeira vez no projeto que uma correlação sobrevive a um salto assíncrono entre Engines.

### Caminhos de falha

| Requisição | Status | Evento publicado |
|---|---|---|
| Parâmetro obrigatório faltando | **`422`** | `planning.failed` |
| `kind` fora do enum | `400` | **nenhum** — o Planner nem é chamado |
| Sem `objective` | `400` | nenhum |
| Sem token | `401` | nenhum |

Payload real capturado no Redis (`redis-cli psubscribe "planning.*"`):

```json
{"intentId":"265d4c2b-…","correlationId":"0aa2b3cf-…","tenantId":"03b48deb-…",
 "workspaceId":"59dd826b-…","reason":"missing_required_parameter",
 "detail":"A Intent não trouxe: sistema. Exigidos por playbooks/nova-implantacao.md."}
```

O `detail` aponta o Playbook responsável — é o que transforma `planning.failed` numa fila de conhecimento de negócio a documentar, não só num erro.

### Cenário 7 — a perda de evento, demonstrada e não escondida

Este é o Risco 2 da Proposal, executado de propósito:

```
Missions antes:  5
docker compose stop mission-worker
POST /intents → HTTP 202          ← o usuário recebe sucesso
Missions depois: 5                ← nenhuma criada
docker compose start mission-worker
Missions com aquele correlationId: 0   ← a perda é DEFINITIVA
```

**Uma Intent planejada com o worker fora do ar não vira Mission, ninguém percebe, e religar não recupera.** O Planner é sem estado ([ADR-0095](../adr/0095-planner-sem-estado.md)) e quem chamou já recebeu `202`. Redis pub/sub não guarda mensagem para assinante ausente — limitação confirmada com evidência desde a [Release 4.5](0004.5-platform-validation-validation-report.md), agora com consequência visível de produto.

### Critério 8 — a Release 5.5 não regrediu

`POST /missions` (Plan escrito à mão) → `201`, com `plan.source: manual`; `GET /missions/{id}` → `200`. As duas portas coexistem, e `source` é o que as distingue depois.

## Testes

| Pacote/Serviço | Testes | Assertions | Variação |
|---|---|---|---|
| `packages/core` | 8 | 13 | — |
| `packages/kernel` | 44 | 103 | — |
| `packages/identity-engine` | 72 | 119 | — |
| `packages/memory-engine` | 55 | 157 | — |
| `packages/mission-engine` | 51 | 161 | — |
| **`packages/planner-engine`** | **15** | 112 | novo |
| `services/event-bus` | 10 | 17 | — |
| `services/gateway` | **28** | 72 | +13 |
| `services/auth` | 12 | 38 | — |
| `services/memory-worker` | 1 | 2 | — |
| **`services/mission-worker`** | **2** | 3 | novo |

**Total: 298 testes, 797 assertions, todos passando, 0 `Skipped`.** Eram 268 antes desta Release.

Destaques do que é coberto:

- Os sete `IntentKind` produzindo Plan não vazio com `source: planner`.
- As três causas de `PlanningFailed`, **incluindo a ordem** (`unknown_intent_kind` vence `missing_required_parameter` quando ambas se aplicam).
- Todo `PlanTemplate` aponta para um Playbook **que existe no repositório** — verificado por `assertFileExists`.
- Todo template preserva ao menos um passo de nível 3 (o ponto de decisão humana do Playbook não se perde na tradução).
- Nenhum passo declara Capability (os Playbooks não as nomeiam).
- O Plan é idêntico independentemente do `autonomyCeiling` — o Planner marca, nunca compara.

## Architecture Validation

- **`composer.lock` de `planner-engine` não lista nenhum Engine** — só `sigma/core`, `sigma/kernel` e as transitivas do próprio Kernel (`symfony/yaml` e dois polyfills).
- Nenhum `use` em `packages/planner-engine/src` resolve para `mission-engine` ou `intent-engine`.
- **`packages/planner-engine` não tem `Infrastructure/`** — três camadas DDD, não quatro ([ADR-0095](../adr/0095-planner-sem-estado.md)). Nenhuma migration, nenhuma tabela.
- `packages/mission-engine` **não** ganhou dependência de `planner-engine`; a mudança lá foi um caso de uso novo em `Application/`, nenhuma linha de `Domain/`.
- `docker/mission-worker.Dockerfile` não copia `packages/planner-engine`.

### Critério 5 — ADR-0096 provada na prática

`services/gateway/tests/PlannerToMissionContractTest.php` leva o payload real publicado pelo Planner direto a `CreateMissionFromPlannedEvent`, **sem nenhuma adaptação**, e verifica que a Mission nasce válida — para os **sete** templates, não só um. Também verifica que o `correlationId` sobrevive até a Mission relida do banco, e que um payload sem `correlationId` é rejeitado.

Este teste vive no gateway porque é o único lugar do monorepo que enxerga os dois pacotes. É ele que impede a divergência que [ADR-0096](../adr/0096-plan-duplicado-por-bounded-context.md) aceitou como custo: **a garantia é de processo, não estrutural** — nada no compilador impede alguém de acrescentar um campo em um lado só; o que quebra é este teste.

## Coverage

**Não medida nesta Release** — explicitamente, não por omissão.

## Scenario Validation

- ✅ **1.** Os sete `IntentKind` produzem Plan não vazio, `source: planner` (automatizado + contra MariaDB real).
- ✅ **2.** As três causas de `PlanningFailed` disparam na ordem especificada.
- ✅ **3.** O ciclo fecha: `POST /intents` → Mission consultável, contra Docker real, sem ninguém escrever os passos.
- ✅ **4.** O `correlationId` atravessa toda a cadeia sem mudar.
- ✅ **5.** O payload é aceito por `CreateMission` sem adaptação, nos sete templates.
- ✅ **6.** Nenhum `PlanStep` sem descrição no Playbook — registrado template a template no Decision Log.
- ✅ **7.** `planner-engine` sem dependência de Engine e sem persistência.
- ✅ **8.** Cenário do worker parado executado; perda documentada como limitação real.
- ✅ **9.** Suíte completa verde, 298/298, 0 pulados.
- ✅ **10.** Decision Log e Validation Report publicados.

## Pendências

1. **`autonomyCeiling` fixo em `0` no `POST /intents`** — o Identity Engine não expõe o nível configurado por User/Role ([ADR-0068](../adr/0068-autonomy-por-capability.md), aberto desde a 3B). Consequência prática: **toda Mission criada por essa rota para em `PendingApproval` no primeiro passo de nível ≥ 1**. Erra deliberadamente para o lado seguro. É a pendência mais visível desta Release.
2. **Perda de evento com o worker fora do ar é definitiva** (cenário 7). Redis Streams resolveria; fora de escopo.
3. **Os `requiredParameters` dos sete templates foram derivados de prosa** — nenhum Playbook nomeia parâmetros. Ponto mais frágil desta Release; merece revisão de quem é dono dos Playbooks.
4. **`candidateCapability` é `null` nos 32 passos** — nenhum Playbook nomeia Capabilities; Capability Registry é a Release 18.
5. **Nada consome `planning.failed`** — Audit Engine (Release 11) é o natural.
6. **Payload inválido no worker é perdido** — sem retry, sem dead-letter. Mesma limitação do `memory-worker`.
7. **`Identifier` duplicada pela quarta vez** — dívida aberta há quatro Releases, distinta da duplicação deliberada de `Plan` (ADR-0096).
8. **`mission-worker` sem healthcheck** — exceção consciente de ADR-0094; sua saúde real (está consumindo?) não é observável.
9. **Nenhuma Mission avança além de `created`/`PendingApproval`** — sem Agent (Release 9), Skill (8) e Execution (10), nada executa as Subtasks. Esperado, não defeito.
10. **PHP 8.4 não validado; coverage não medida.**
