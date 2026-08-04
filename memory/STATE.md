# Estado atual do projeto

_Atualizado em: 2026-08-04._

## Fase

**Release 3.5 — Architecture Consolidation: COMPLETA.** Não mudou o produto — fortaleceu a base antes da Memory Engine. Roadmap estendido de 14 para 24 Releases (visão de 5-10 anos do Product Owner). Release 3 (3A+3B) segue completa e com push feito. Próximo passo: Release 4 — Memory Engine, reconhecida como o segundo marco mais importante do projeto.

## O que existe (documentação)

- Visão, produto, filosofia, [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), [SGL.md](../SGL.md), [DIGITAL_TWIN.md](../DIGITAL_TWIN.md), [BOOTSTRAP.md](../BOOTSTRAP.md), [SYSTEM_MANIFEST.md](../SYSTEM_MANIFEST.md), [KERNEL.md](../KERNEL.md), [COMPATIBILITY.md](../COMPATIBILITY.md), [IDENTITY_MODEL.md](../IDENTITY_MODEL.md), [IDENTITY_LIFECYCLE.md](../IDENTITY_LIFECYCLE.md), [DOMAIN_EVENTS.md](../DOMAIN_EVENTS.md), **[EVENT_CATALOG.md](../EVENT_CATALOG.md)** (novo), **[CHANGELOG.md](../CHANGELOG.md)** (novo, orientado ao usuário), `contracts/`, `docs/rfc/`, `sdk/`.
- **[ROADMAP.md](../ROADMAP.md) estendido a 24 Releases** — 5 Engines novos (Knowledge=16, Digital Twin=17, Capability Registry=18, Council=19, Multi-Agent Runtime=20), Gateway/API própria (12), 5 componentes estruturais sinalizados (Scheduler/Secrets Manager/Cache Layer/Observability/Policy Engine).
- **78 ADRs** — [docs/adr/](../docs/adr/). Novas nesta rodada (0070-0078): roadmap estendido, EVENT_CATALOG obrigatório, CredentialProvider, Identity independe de autenticação (confirmação), Session é Aggregate autônomo (confirmação), Workspace/Context→Session (direção adiada), metadata de evento (direção adiada), VERSION.md+SemVer por Engine, CHANGELOG.md.
- **[packages/identity-engine/VERSION.md](../packages/identity-engine/VERSION.md)** (novo) — primeiro Engine com Semantic Versioning formalizado.
- Releases 2, 3A, 3B, 3.5: Proposal + Decision Log + Validation Report completos para todas.

## O que existe (código)

Igual à Release 3B, com uma renomeação: **`PasswordHasher` → `CredentialProvider`** (`Argon2idPasswordHasher` → `Argon2idCredentialProvider`) em `packages/identity-engine` — renomeação pura, sem mudança de comportamento (ADR-0072). `contracts/Identity.contract.yaml` corrigido (events reais, output `Context` não `Identity`) via validação cruzada.

**Total: 135 testes automatizados, todos passando** (8+36+6+8+72+5) — confirmados também via `docker compose down -v` + `build --no-cache` + `up`, ambiente genuinamente limpo.

## Achado real desta rodada

Migrations do Identity Engine só rodam na **primeira requisição HTTP** ao `services/auth` (dentro de `IdentityEngineModule::boot()`, disparado só quando `Bootstrap` roda) — não há hook de inicialização de container. Descoberto ao testar em ambiente Docker genuinamente limpo (`down -v`), quando um seed via `docker exec` falhou antes de qualquer requisição HTTP ter acontecido. Não corrigido (mudaria comportamento, fora do escopo "sem mudar comportamento" desta Release) — documentado como característica conhecida, sinalizado para quando um Engine futuro com persistência for desenhado.

## Pendências / riscos sinalizados

- **PHP local é 8.2, ADR-0009 pede 8.4** — adiado para a Release de CI/CD.
- Divergência `autonomy_level_required` (numérico) vs. `autonomyCapabilities` (nomeado) — reconciliação adiada para o Skill Engine (Release 8).
- `PermissionId` sem uso na Infrastructure.
- `IDENTITY_MODEL.md` ainda descreve `Context` como objeto de mais alto nível, não `Identity` — mantido por princípio (ADR-0064 documenta a divergência, texto original não reescrito).
- Migrations lazy na primeira requisição, não no startup do container (achado desta rodada, ver acima).
- Duas direções aprovadas, não implementadas: Workspace/Context migrarem de `Identity` para `Session` (ADR-0075); metadata padrão em eventos de domínio (ADR-0076) — aguardando a próxima vez que o Identity Engine for tocado com mudança de comportamento real.
- Numeração Release 6/7 (Planner/Intent) — mantida conforme ADR-0031, diverge da tabela recebida do Product Owner; sinalizado em ROADMAP.md, aguardando confirmação.
- **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.
- Backlog sinalizado, não implementado: `/health/details`, `LogContext`, validação de Bootstrap para dependência ausente, endpoint HTTP para `RegisterIdentity`.

## Bloqueios

Nenhum. Push do commit da Release 3.5 aguardando confirmação. Próximo passo: Proposal da Release 4 — Memory Engine. Ver [NEXT.md](../memory/NEXT.md).
