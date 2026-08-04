# Como contribuir com o SIGMA

## O processo é por Release, não por PR isolado

O SIGMA avança uma Release por vez, seguindo a ordem definida no [ROADMAP.md](ROADMAP.md) (ver [ADR-0010](docs/adr/0010-processo-por-epicos-com-aprovacao.md), [ADR-0015](docs/adr/0015-roadmap-por-camadas-nao-por-feature.md) e [ADR-0024](docs/adr/0024-terminologia-release.md) — "Release" substitui "Sprint"/"épico" como termo corrente, mesmo onde ADRs anteriores ainda dizem "épico"). Antes de escrever código para uma Release, ela precisa ter sido apresentada e aprovada no formato:

- **Objetivo** — o que a Release resolve.
- **Escopo** — o que entra e, explicitamente, o que não entra.
- **Arquitetura** — como se encaixa no domínio já existente (Engines, entidades, eventos envolvidos — conforme [SIGMA_PROTOCOL.md](SIGMA_PROTOCOL.md)).
- **Dependências** — o que precisa existir antes.
- **Riscos** — o que pode dar errado e como é mitigado.
- **Entrega** — o incremento concreto e verificável que a Release produz.
- **Testes** — o que será coberto e como.
- **Critérios de Aceite** — como se sabe que a Release está pronta.

Sem essa aprovação, não há branch de implementação — apenas documentação e discussão são aceitas.

## Fluxo de trabalho

1. Branch a partir de `main`: `release/<numero>-<slug>` para código de Release aprovada (ex: `release/2-kernel`), `docs/<slug>` para documentação, `fix/<slug>` para correção pontual.
2. Commits seguindo [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.
3. Pull Request descrevendo a que Critério de Aceite da Release ele atende.
4. Revisão contra [docs/conventions/coding-standards.md](docs/conventions/coding-standards.md) e [docs/conventions/naming-conventions.md](docs/conventions/naming-conventions.md).
5. Merge somente após aprovação explícita.

## Decisões arquiteturais

Toda decisão que afeta mais de um módulo, introduz uma dependência nova, ou muda um contrato já estabelecido, vira uma ADR — ver [docs/adr/template.md](docs/adr/template.md) e [docs/adr/README.md](docs/adr/README.md). Uma ADR não é revogada por edição; decisões que mudam geram uma nova ADR referenciando a anterior.

## Padrão de código

Todo código de aplicação segue [docs/conventions/coding-standards.md](docs/conventions/coding-standards.md): documentação do porquê, testes, logs, tratamento de erro, versionamento de contratos públicos, nomes padronizados, alta legibilidade, comentários apenas quando o "porquê" não é óbvio.

## Documentação que não é código

`agents/`, `skills/`, `knowledge/`, `playbooks/` e `council/` são documentação viva, editável fora do ciclo de épico — não exigem a aprovação formal de um épico para receber atualização, mas ainda seguem revisão por Pull Request. `memory/` é atualizada por quem (humano ou Agent) estiver ativamente trabalhando no projeto, para manter [memory/STATE.md](memory/STATE.md) e [memory/NEXT.md](memory/NEXT.md) refletindo a realidade.

Um `manifest.json` novo ou alterado em `plugins/` segue o schema de [plugins/manifest.schema.json](plugins/manifest.schema.json) e referencia uma Skill já documentada em `skills/` — nunca introduz um Plugin sem Skill de domínio correspondente.

## Código de conduta

Contribuições seguem o [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
