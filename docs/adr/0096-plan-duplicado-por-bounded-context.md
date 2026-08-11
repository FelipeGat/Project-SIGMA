# ADR-0096: `Plan`/`SubtaskCandidate` são duplicados por fronteira de contexto, não compartilhados via `packages/core`

- **Status**: Aceito — responde a uma pergunta deixada em aberto por [ADR-0092](0092-plan-e-conceito-proprio-do-mission-engine.md)
- **Data**: 2026-08-11

## Contexto

A [ADR-0092](0092-plan-e-conceito-proprio-do-mission-engine.md) estabeleceu que `Plan` e `SubtaskCandidate` são Value Objects do próprio `packages/mission-engine`, nunca importados de `planner-engine`. E deixou explicitamente para este momento a pergunta inversa:

> *"Quando o Planner Engine (Release 6) for modelado, seu próprio `PLANNER_MODEL.md` decide se ele reaproveita o Value Object `Plan` de `packages/mission-engine` (via `packages/core`, movido lá) ou mantém sua própria representação interna que só se torna o `Plan` do Mission Engine no momento de publicar `mission.planned`."*

A tensão é real e tem argumento dos dois lados. Contra duplicar: o projeto já carrega `Identifier` duplicada em três pacotes, sinalizada como pendência há três Releases — duplicar mais uma coisa parece repetir um erro reconhecido. A favor de duplicar: mover `Plan` para `packages/core` exigiria mudar `packages/mission-engine`, um pacote de Release já concluída e validada.

## Decisão

Cada Engine mantém sua própria `Plan`/`SubtaskCandidate`. Nenhuma vai para `packages/core`. A igualdade de forma entre as duas é garantida por [contracts/Planner.contract.yaml](../../contracts/Planner.contract.yaml), não por dependência de pacote.

A tradução acontece uma única vez, no payload de `mission.planned`.

## Consequências

- **É a decisão correta de DDD, não uma acomodação.** Planner e Mission são bounded contexts distintos. Para o Planner, um `SubtaskCandidate` é *o que ele decidiu*; para o Mission Engine, é *o que recebeu e vai executar*. São o mesmo formato descrevendo conceitos diferentes — exatamente o caso em que DDD manda separar os modelos e traduzir na fronteira.
- **Compartilhar acoplaria as evoluções.** Um campo que o Planner precisasse acrescentar passaria a exigir mudança no Mission Engine, e vice-versa — o mesmo acoplamento que a ADR-0092 recusou, apenas na direção oposta.
- **Não é o caso `Identifier`, e a distinção importa**: `Identifier` é encanamento sem significado de domínio, e sua duplicação continua sendo uma pendência legítima a resolver. `Plan` é fronteira de contexto. Consolidar os dois casos com a mesma régua seria confundir "código repetido" com "modelo por contexto".
- **Custo aceito: as duas formas podem divergir por descuido.** Nada no compilador impede alguém de acrescentar um campo em um lado só. A mitigação é o contrato e a Architecture Validation da Release 6B, que precisa verificar a igualdade explicitamente — não é uma garantia estrutural, e o Validation Report deve dizer isso.
- **`packages/mission-engine` não é tocado.** Uma Release concluída e validada permanece intacta.
