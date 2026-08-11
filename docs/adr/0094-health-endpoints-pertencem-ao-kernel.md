# ADR-0094: Health endpoints pertencem ao Kernel, não a cada service

- **Status**: Aceito
- **Data**: 2026-08-11

## Contexto

[ADR-0042](0042-health-estilo-kubernetes.md) definiu os três endpoints de health (`/health/live`, `/health/ready`, `/health/startup`) como o padrão do SIGMA para todo processo deployável. A Release 2 os implementou — em `Sigma\Gateway\HealthEndpoints`, dentro de `services/gateway`.

Duas coisas ficaram visíveis ao operar o sistema de fora pela primeira vez, na [Release 5.5](../releases/0005.5-vertical-slice-validation-report.md):

1. **`services/auth` não tem endpoint de health nenhum.** Está no ar desde a Release 3B, é um processo deployável como qualquer outro, e não expõe `live`, `ready` nem `startup`. Não por decisão registrada — simplesmente porque a implementação nasceu dentro do gateway e não havia como reusá-la sem copiar.

2. **A última consequência declarada da ADR-0042 nunca se concretizou.** Ela afirma que "`docker/docker-compose.yml` e qualquer definição de deploy futura (Release 2 em diante) já apontam para estes três endpoints". Não apontam: nenhum serviço do SIGMA tem `healthcheck` no compose — só `redis` e `mariadb`, que são imagens de terceiros com healthcheck próprio. Os três endpoints existem, respondem corretamente, e **nada os consome**.

O efeito combinado é que o `restart: on-failure` adicionado na Release 5.5 reage a um processo que *morre*, nunca a um que ficou `not_ready`. Um gateway com o banco fora do ar continua recebendo tráfego, e a granularidade `degraded` por Module — desenhada em [ADR-0041](0041-lifecycle-estendido.md) e implementada com cuidado — não influencia nenhuma decisão operacional real.

A força em tensão: health é comportamento de **Module**, não de service. Todo processo do SIGMA sobe pelo mesmo `LifecycleManager` e tem um `HealthManager`; replicar a camada HTTP que o expõe em cada service novo repete o padrão que o projeto já lamenta em `Identifier` (duplicada em três pacotes, ver `memory/NEXT.md`).

## Decisão

`HealthEndpoints` e `BootFailureEndpoints` migram de `Sigma\Gateway\` para `packages/kernel` (`Sigma\Kernel\Http\`), passando a ser parte da Kernel API que todo service consome — não código que cada service reimplementa. Todo processo deployável do SIGMA expõe os três endpoints de ADR-0042, e o `docker-compose.yml` os consome via `healthcheck`.

A política que `BootFailureEndpoints` encarna passa a valer para todo o sistema, não só para o gateway: **falha de boot por dependência externa indisponível é `not_ready`, nunca `not_live`** — porque `/health/live` decide se o orquestrador reinicia o processo, e reiniciar não traz um banco de volta.

`services/memory-worker` é a exceção explícita: não escuta HTTP (decisão da Release 4B), então não expõe endpoint algum. Seu healthcheck permanece o que já é — o processo estar vivo, coberto por `restart: on-failure`.

## Consequências

- **Todo service novo ganha health de graça.** O Skill Engine (Release 8), o Agent Engine (9) e qualquer service futuro não precisam decidir nada sobre health nem copiar código: instanciam o que o Kernel oferece.
- **`services/auth` deixa de ser um ponto cego operacional** — hoje não há como um orquestrador saber se ele está pronto.
- **O `degraded` por Module passa a ter efeito real**, e não apenas a ser observável: um Module degradado tira o processo do tráfego via `healthcheck`, que era a intenção original de ADR-0041/0042.
- **Custo aceito**: o Kernel ganha uma pasta `Http/`, o que o aproxima de um framework. Mitigado por escopo estrito — as duas classes traduzem `HealthManager` para pares `[status, Envelope]`, sem roteamento, sem middleware, sem conhecer SAPI. Nenhuma delas conhece Engine, Mission ou qualquer conceito de domínio, o que preserva [ADR-0040](0040-bootstrap-nao-conhece-engines.md).
- **Custo aceito**: `services/gateway` passa a importar de `packages/kernel` algo que hoje é seu. É a direção correta — `Sigma\Gateway\HealthEndpoints` sempre foi Kernel morando no lugar errado.
- **Não revoga ADR-0042.** Corrige a consequência dela que nunca foi implementada, e a estende a todo processo deployável.
- **Breaking change interno**: qualquer código que importe `Sigma\Gateway\HealthEndpoints` quebra. Hoje isso é só `services/gateway/public/index.php` e seus testes.
