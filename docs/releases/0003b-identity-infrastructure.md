# Release 3B — Identity Infrastructure

Segunda metade da Release 3 — Identity Engine ([ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)). **Não é uma Proposal ainda** — este documento é um placeholder deliberado, registrando o que se sabe hoje sobre 3B sem detalhar Escopo/Arquitetura/Testes, que só são escritos como Proposal formal (mesmo formato de [0003a-identity-domain.md](0003a-identity-domain.md)) depois que a [Release 3A — Identity Domain](0003a-identity-domain.md) estiver implementada e validada. Escrever a Proposal completa de 3B agora, antes de 3A existir em código, correria o mesmo risco que a divisão 3A/3B existe para evitar: desenhar infraestrutura em cima de um modelo que ainda pode mudar.

## O que já se sabe

- Camadas `Application/`, `Infrastructure/`, `Interface/` de `packages/identity-engine` (ver [ADR-0061](../adr/0061-engine-quatro-camadas-ddd.md)), consumindo o `Domain/` já validado por 3A.
- Persistência real (MariaDB) das dez entidades de [IDENTITY_MODEL.md](../../IDENTITY_MODEL.md) — primeira Release do projeto com banco.
- `IdentityEngineModule implements IModule` — primeiro `IModule` de domínio real (Engine, não infraestrutura pura), registrado no System Manifest.
- Publicação real dos eventos de [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) via `IEventBus`.
- `services/auth` — casca HTTP mínima (login/logout/resolução de Identity), autenticação real (hash de senha, emissão/validação de token de Session).
- `docker-compose.yml` ganha `mariadb` — e, diferente da Release 2, o build precisa ser validado de fato (não repetir a pendência já registrada no [Validation Report da Release 2](0002-sigma-bootstrap-validation-report.md)).
- Decision Log e Validation Report próprios (`docs/releases/0003b-identity-infrastructure-decision-log.md`/`-validation-report.md`).

## O que ainda não está decidido

- Camada de acesso a dados (PDO puro + runner de migration vs. Doctrine DBAL) — pergunta já registrada na Proposal original, permanece em aberto para a Architecture Review de 3B.
- Se `Session`/`Identity` (Value Objects e Aggregate) precisam de algum ajuste depois de 3A rodar de verdade — só se sabe depois que 3A estiver implementada.

## Quando esta Proposal é escrita de fato

Assim que a [Release 3A — Identity Domain](0003a-identity-domain.md) tiver Decision Log e Validation Report publicados — este documento é então substituído por uma Proposal completa, no mesmo formato Objetivo/Escopo/Arquitetura/Dependências/Riscos/Entregáveis/Testes/Critérios de Aceite, submetida à aprovação do Product Owner antes de qualquer código de 3B.
