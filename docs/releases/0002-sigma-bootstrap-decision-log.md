# Release 2 — SIGMA Bootstrap — Decision Log

Decisões locais tomadas durante a implementação, dentro do escopo já aprovado em [0002-sigma-bootstrap.md](0002-sigma-bootstrap.md) (revisão 3). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi entregue

- `packages/core` — `Sigma\Core\Id` (UUID v4 sem dependência externa), `Sigma\Core\SigmaException`. **4 testes.**
- `packages/kernel` — as seis interfaces (`IContainer`, `ILogger`, `IEventBus`, `IModule`, `IConfiguration`, `IHealth`), `Container`, `Logger`, `ConfigurationProvider`, `HealthManager`, `LifecycleManager`, `SystemManifestLoader`/`SystemManifest`/`ModuleReference`, `KernelModule`. **30 testes.**
- `services/event-bus` — `RedisEventBus` (implementa `IEventBus` sobre `predis/predis`), `EventBusModule`. **6 testes.**
- `services/gateway` — `Bootstrap` (monta e sobe o sistema a partir do System Manifest), `HealthEndpoints`, `Envelope`, front controller `public/index.php`. **8 testes + smoke test HTTP real via `php -S`, com Scenario Validation manual (ver abaixo).**
- `contracts/Module.contract.yaml` — primeiro Sigma Contract real do projeto.
- `docker/docker-compose.yml` + `docker/gateway.Dockerfile` — Redis + gateway, sem MariaDB (ADR-0053).
- `system-manifest.yaml` de exemplo na raiz do repositório.
- Primeira linha de [COMPATIBILITY.md](../../COMPATIBILITY.md) já publicada na revisão anterior (Kernel 1.0 / Protocol 1.0 / Plugin API 1.0 ✅).

**Total: 48 testes automatizados, todos passando.**

## Decisões locais e o porquê

### `packages/kernel` é framework-agnóstico — não Laravel

A proposta previa Laravel 12/PHP 8.4 para o backend em geral ([ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md)), mas o Kernel API é definida como interfaces próprias, independentes de framework ([ADR-0052](../adr/0052-kernel-api-apenas-interfaces.md)). Implementá-lo como PHP puro (sem dependência de `illuminate/container` ou qualquer parte do Laravel) mantém `packages/kernel` testável isoladamente e genuinamente reutilizável — o dia em que `services/gateway` precisar de Laravel de verdade (rotas complexas, middleware), ele consome o Kernel como qualquer outro consumidor, sem o Kernel depender do framework que o usa.

### `services/gateway` não usa Laravel nesta Release

A superfície desta Release é três endpoints de health. Instalar o esqueleto completo do Laravel (bootstrap, service providers, roteamento) para isso seria infraestrutura sem propósito imediato — o mesmo raciocínio que já tirou o banco de dados do escopo ([ADR-0053](../adr/0053-escopo-restrito-release-2.md)). Implementado como front controller mínimo (`public/index.php`) com roteamento manual. **Alternativa descartada**: instalar Laravel completo agora "para não ter que migrar depois" — rejeitada porque adicionaria massa e complexidade sem nenhum Critério de Aceite desta Release exigindo isso. `services/gateway` ganha Laravel de fato quando tiver uma API de domínio real para expor (a partir da Release 5 — Mission Engine, provavelmente).

### Event Bus: entrega local síncrona além da publicação Redis

`RedisEventBus::publish()` publica no canal Redis **e** entrega, de forma síncrona, a qualquer handler registrado via `subscribe()` no mesmo processo. Um listener Redis real (`pubSubLoop`, cross-processo, bloqueante) não foi implementado — não há, nesta Release, nenhum Engine ou Plugin rodando em processo separado para consumir eventos de verdade. **Simplificação conhecida**, registrada aqui para não ser esquecida: quando o primeiro consumidor cross-processo existir (provavelmente Release 4 — Memory Engine reagindo a eventos), este componente precisa de um loop de consumo real, e este Decision Log é a evidência de que isso foi uma escolha deliberada, não um esquecimento.

### `ConfigurationProvider` valida no `register()`, não apenas conceitualmente no "boot"

BOOTSTRAP.md descreve a validação de configuração como parte da etapa `boot`. Na implementação, `ConfigurationProvider` valida na própria construção, chamada dentro de `register()` de cada Module — que roda antes de `boot()` no `LifecycleManager`. O efeito prático (falhar antes de qualquer `start`) é o mesmo; a falha só acontece um passo mais cedo do que a descrição conceitual sugeria. Não é uma divergência de comportamento, é uma precisão da implementação — **BOOTSTRAP.md não precisou ser corrigido**, a descrição já era compatível com isso.

### Achado real durante Scenario Validation: `$_ENV` não é confiável

Ao testar `services/gateway` com um servidor HTTP real (`php -S`), variáveis de ambiente passadas ao processo **não apareciam em `$_ENV` nem em `$_SERVER`** sob o servidor embutido do PHP nesta instalação (XAMPP, `variables_order` sem `E`). `getenv()` sem argumentos, por outro lado, sempre retorna o ambiente completo, independente dessa configuração de `php.ini`. `public/index.php` foi corrigido para usar `getenv()` como fonte. **Isso é exatamente o tipo de coisa que a Scenario Validation existe para pegar** — um teste unitário com um array de configuração injetado manualmente nunca teria revelado esse problema.

### Ambiente local: PHP 8.2, não PHP 8.4

[ADR-0009](../adr/0009-stack-tecnologica-de-referencia.md) especifica PHP 8.4. O ambiente de desenvolvimento disponível (XAMPP) tem PHP 8.2.12. Todo código desta Release foi escrito compatível com 8.2 (nenhum recurso exclusivo de 8.3/8.4 usado) e os 48 testes rodam de fato nesse ambiente. **Risco sinalizado, não resolvido nesta Release**: o ambiente de produção/CI precisa rodar PHP 8.4 real antes de qualquer deploy — isso não foi validado aqui.

### `docker-compose`/`Dockerfile` escritos, build não verificado

O Docker Desktop não estava em execução no ambiente desta sessão (CLI presente, daemon indisponível) — `docker compose up --build` não pôde ser executado. `docker/docker-compose.yml` e `docker/gateway.Dockerfile` foram escritos seguindo o padrão já usado no projeto, mas **não foram validados por um build real**. Isso é uma lacuna explícita da Scenario Validation desta Release — recomenda-se validar antes de considerar a Release definitivamente encerrada.

## Os três níveis de validação (ADR-0054)

1. **Testes Automatizados**: 48 testes, `composer test` em cada um dos quatro pacotes/serviços — todos passando.
2. **Architecture Validation**: nenhum Module importa classe concreta do Kernel fora das seis interfaces; nenhuma ramificação de código depende de `kind`; `contracts/Module.contract.yaml` publicado e consistente com `IModule`; nenhuma entidade de domínio, banco de dados ou autenticação presente no diff.
3. **Scenario Validation** — executados manualmente via `php -S` + `curl` contra o front controller real:
   - ✅ Sistema sobe com todos os módulos declarados presentes → `/health/ready` 200.
   - ✅ Módulo obrigatório ausente do registro → boot falha explicitamente (coberto em teste automatizado + já validado na Release anterior via `LifecycleManagerTest`).
   - ✅ Módulo entra em `degraded` depois de `ready` → `/health/ready` reflete por módulo, sistema não cai (teste automatizado).
   - ✅ System Manifest com erro (arquivo inexistente) → 503 com erro explícito, testado via HTTP real.
   - ✅ Módulo com versão incompatível → rejeitado antes de `ready` (teste automatizado).
   - ✅ `/health/live`, `/health/ready`, `/health/startup` corretos em cada fase — testado via HTTP real e via testes automatizados.
   - ⚠ Subida via `docker-compose` — **não executada** (ver acima).

## Impacto para as próximas Releases

- Release 3 (Identity Engine) é o primeiro Module de domínio real a implementar `IModule` — vai validar se o contrato genérico se sustenta fora de infraestrutura pura, risco já sinalizado na proposta.
- Release 3 também é onde MariaDB entra no ambiente local pela primeira vez.
- O padrão de `ConfigurationProvider` por Module, testado aqui com `event-bus`, deve ser reaproveitado sem alteração.
