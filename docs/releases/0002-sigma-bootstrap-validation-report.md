# Release 2 — SIGMA Bootstrap — Validation Report

Prova de execução da fase de Validation (ver [ADR-0048](../adr/0048-processo-quatro-fases.md)). Documento criado retroativamente, aplicando o padrão do [ADR-0056](../adr/0056-validation-report-obrigatorio.md) — a Release 2 já estava implementada e aprovada quando esse padrão passou a ser exigido; os dados abaixo são os mesmos já coletados durante a implementação e citados no [Decision Log](0002-sigma-bootstrap-decision-log.md), agora reorganizados neste formato fixo. Proposta: [0002-sigma-bootstrap.md](0002-sigma-bootstrap.md) (revisão 3).

## Release

Release 2 — SIGMA Bootstrap.

## Ambiente

- Windows 10 Pro, XAMPP.
- Execução original: 2026-08-04.

## PHP

- Versão-alvo ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)): `8.4`.
- Versão efetivamente usada: `8.2.12`.
- Gap sinalizado e aceito conscientemente pelo Product Owner — não bloqueia a Release 2; reconciliação adiada para a Release de CI/CD, quando o ambiente inteiro é padronizado de uma vez.

## Docker

`docker compose up --build` **não foi executado**. O Docker Desktop não estava em execução no ambiente desta sessão (CLI presente, `docker --version` respondia, mas o daemon não — `docker compose up` falhava ao tentar conectar em `npipe:////./pipe/dockerDesktopLinuxEngine`). `docker/docker-compose.yml` e `docker/gateway.Dockerfile` foram escritos seguindo o padrão já usado no projeto, mas sem validação de build real. Aceito conscientemente pelo Product Owner, condicionado a este registro explícito.

## HTTP

Testado via `php -S` (servidor de desenvolvimento embutido do PHP) contra `services/gateway/public/index.php` real, com `curl`:

| Endpoint | Cenário | Status obtido |
|---|---|---|
| `/health/live` | Sistema no ar | `200` |
| `/health/ready` | Todos os módulos declarados presentes e prontos | `200` |
| `/health/startup` | Startup completo | `200` |
| `/rota-inexistente` | Rota não mapeada | `404` |
| `/health/ready` | System Manifest apontando para arquivo inexistente | `503`, corpo com erro explícito |

## Testes

| Pacote/Serviço | Comando | Testes | Assertions |
|---|---|---|---|
| `packages/core` | `composer test` (PHPUnit) | 4 | 5 |
| `packages/kernel` | `composer test` (PHPUnit) | 30 | 73 |
| `services/event-bus` | `composer test` (PHPUnit) | 6 | 12 |
| `services/gateway` | `composer test` (PHPUnit) | 8 | 22 |

**Total**: 48 testes, todos passando.

## Coverage

Não medida nesta Release — nenhuma ferramenta de coverage (Xdebug/PCOV) configurada no ambiente local desta sessão.

## Scenario Validation

Cenários listados na proposta (revisão 3), cada um com o resultado real:

- ✅ Sistema sobe com todos os módulos declarados presentes → `/health/ready` 200 — validado via HTTP real.
- ✅ Módulo obrigatório declarado no Manifest mas ausente do registro → boot falha explicitamente com `lifecycle.required_module_missing` — coberto em `LifecycleManagerTest`.
- ✅ Módulo entra em `degraded` depois de `ready` → refletido por módulo em `/health/ready`, sistema não cai por inteiro — coberto em `LifecycleManagerTest`.
- ✅ System Manifest apontando para arquivo inexistente → `/health/ready` responde `503` com erro explícito no corpo — validado via HTTP real.
- ✅ Módulo com `minVersion` incompatível → rejeitado antes de `ready`, com `lifecycle.incompatible_module_version` — coberto em `LifecycleManagerTest`.
- ✅ Dependência circular entre Modules → rejeitada com `lifecycle.circular_dependency` — coberto em `LifecycleManagerTest`.
- ✅ `/health/live`, `/health/ready`, `/health/startup` corretos em cada fase — validado via HTTP real e testes automatizados.
- ⚠ Subida via `docker-compose` — **não executada** (ver "Docker" acima).

**Achado real durante Scenario Validation**: sob o servidor embutido do PHP nesta instalação (XAMPP, `variables_order` sem `E`), variáveis de ambiente passadas ao processo não apareciam em `$_ENV`/`$_SERVER`. `public/index.php` usava `$_ENV` como fonte de configuração — corrigido para `getenv()`, que retorna o ambiente completo independente dessa configuração de `php.ini`. Não pego por nenhum teste automatizado; só apareceu ao testar com HTTP real. Detalhado no [Decision Log](0002-sigma-bootstrap-decision-log.md).

## Pendências

- `docker-compose up --build` não verificado por build real — aceito conscientemente pelo Product Owner, condicionado a este registro.
- PHP 8.2 em vez de 8.4 (ADR-0009) — aceito conscientemente pelo Product Owner, reconciliação adiada para a Release de CI/CD.
- Nenhum listener Redis real (cross-processo, via `pubSubLoop`) — `RedisEventBus` publica no Redis e entrega local sincronamente no mesmo processo; sem consumidor cross-processo ainda para justificar o listener real. Registrado como simplificação conhecida no Decision Log; revisitado (reorganizado, não resolvido) no [ADR-0057](../adr/0057-eventbus-composicao-inmemory.md).
- Coverage de código não medido.
