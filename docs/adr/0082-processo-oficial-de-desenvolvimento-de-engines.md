# ADR-0082: Processo Oficial de Desenvolvimento de Engines do SIGMA

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

A Release 3 (Identity Engine) e a Release 4 (Memory Engine) seguiram, na prática, a mesma sequência antes de qualquer código: pesquisa sobre o que já existia em prosa espalhada pelo repositório, um documento de Modelo (`IDENTITY_MODEL.md`/`MEMORY_MODEL.md`), um documento de Lifecycle (`IDENTITY_LIFECYCLE.md`/`MEMORY_LIFECYCLE.md`), um catálogo de eventos de domínio (`DOMAIN_EVENTS.md`/`EVENT_CATALOG.md`), um Sigma Contract, uma Proposal formal, Implementation, Validation, uma revisão do Product Owner, e só então o push. Essa sequência nunca foi escrita como regra — era um padrão seguido por repetição, reconhecido explicitamente pelo Product Owner na revisão da Release 4A ("Ela não implementou código. Ela modelou. Era exatamente o que eu queria"), que pediu que ela se tornasse obrigatória para todo Engine daqui em diante.

## Decisão

A partir da Release 4 (inclusive), todo Engine que modela domínio novo segue, nesta ordem, o **Processo Oficial de Desenvolvimento de Engines do SIGMA**:

```
Research → Manifesto → Model → Lifecycle → Events → Contract → Proposal → Implementation → Validation → Review → Push
```

1. **Research** — levantamento do que já existe em prosa (ADRs, `*.md` de arquitetura já publicados) sobre o domínio do Engine, identificando contradições/ambiguidades antes de escrever qualquer modelo novo (mesmo papel que a pesquisa via subagente cumpriu antes do `MEMORY_MODEL.md`).
2. **Manifesto** — se o Engine ainda não tem uma seção própria dedicada em algum documento de arquitetura de alto nível (o equivalente ao que [ADR-0039](0039-identity-engine.md) fez para Identity), ela é escrita nesta etapa.
3. **Model** — `<ENGINE>_MODEL.md`: entidades, atributos, identificadores, relações.
4. **Lifecycle** — `<ENGINE>_LIFECYCLE.md`: como as entidades nascem, transitam, terminam.
5. **Events** — seção nova em `DOMAIN_EVENTS.md` e `EVENT_CATALOG.md`, catalogados antes do código.
6. **Contract** — `contracts/<Engine>.contract.yaml`.
7. **Proposal** — no formato de [ADR-0010](0010-processo-por-epicos-com-aprovacao.md), podendo se dividir em sub-Releases Domain/Infrastructure quando [ADR-0060](0060-release-dividida-em-sub-releases.md) se aplicar.
8. **Implementation** — código, seguindo exatamente o que Model/Lifecycle/Events/Contract já definiram; qualquer divergência descoberta durante a Implementation vira uma ADR nova, nunca uma edição silenciosa do Model.
9. **Validation** — os três níveis de [ADR-0054](0054-tres-niveis-de-validacao.md), documentados no Validation Report ([ADR-0056](0056-validation-report-obrigatorio.md)).
10. **Review** — revisão do Product Owner, no papel assumido formalmente por ele desde a Release 4A ("Meu compromisso até a Release 24") — aprovar, rejeitar, exigir mudança, ou pedir uma evolução do modelo (como esta própria revisão fez).
11. **Push** — nunca antes de confirmação explícita na mesma rodada ([memory/STATE.md](../../memory/STATE.md)/regra de governança já vigente, independente desta ADR).

Nenhuma etapa é pulada; nenhum código é escrito antes das etapas 1-7 estarem aprovadas. Etapas puramente documentais (1-6) podem passar por múltiplas revisões antes da Proposal (etapa 7) ser sequer escrita — como aconteceu com o Memory Engine, cujo Model teve uma revisão 2 pedida pelo Product Owner antes mesmo da Proposal 4A ser reapresentada.

## Consequências

- Todo Engine futuro (Mission, Intent, Planner, Agent, Skill, Execution, Audit, Knowledge, Digital Twin como Engine próprio, Capability Registry, Council, Multi-Agent Runtime — ver [ROADMAP.md](../../ROADMAP.md)) tem uma sequência fixa e conhecida de antemão — reduz a chance de um Engine começar a ser codificado antes de seu domínio estar resolvido, o mesmo erro que a Release 3.5 preveniu retroativamente para Identity.
- Aumenta o número de documentos e revisões antes do primeiro código de cada Engine — aceito conscientemente, mesmo trade-off já assumido para a Release 4 ("investir tempo extra na modelagem, mesmo que atrase o cronograma").
- Esta ADR não define quem executa cada etapa (Chief Architect, Product Owner, ambos) — isso já está implícito no processo de governança vigente ([CONTRIBUTING.md](../../CONTRIBUTING.md)) e não precisa de repetição aqui.
- Uma etapa que se revela desnecessária para um Engine específico (ex: um Engine sem eventos de domínio próprios) é documentada como "Não aplicável, porque X" no lugar de ser omitida silenciosamente — mesmo padrão já usado em Validation Reports para seções não aplicáveis a uma sub-Release.
