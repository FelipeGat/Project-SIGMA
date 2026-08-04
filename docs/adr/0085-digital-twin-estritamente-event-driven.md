# ADR-0085: Digital Twin é estritamente Event-Driven — inclusive a primeira população, sem exceção

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[DIGITAL_TWIN.md](../../DIGITAL_TWIN.md) e [MEMORY_MODEL.md](../../MEMORY_MODEL.md) revisão 1 descreviam a sincronização de um `DigitalTwin` em dois caminhos distintos: atualizações via Semantic Event (sempre), mas a **primeira** população via uma Capability de leitura (`GestorSkill.FindClient`) escrevendo diretamente o `state` inicial. O Product Owner, na revisão da Release 4A, declarou explicitamente que "Every Twin is Event Driven. Nunca leitura direta. Sempre. Evento → Projection → Twin" — sem abrir exceção para a primeira população.

## Decisão

Todo `DigitalTwin`, sem exceção, nasce e muda de estado exclusivamente através de `Evento → Projection → Twin`. Não existe um caminho de leitura direta em que o Memory Engine (ou qualquer outro componente) chama uma Capability e escreve o resultado no Twin por conta própria.

Para reconciliar isso com a necessidade real de uma primeira leitura externa (`Client`/`Project`/`Company`, a partir da Release 8): a própria Capability de leitura passa a **publicar um evento** (ex: `client.fetched_via_gestor`) como resultado de sua execução, antes de qualquer Twin existir — a Capability nunca escreve no Twin; ela só causa um evento, e é esse evento que o Memory Engine projeta, exatamente como projetaria qualquer Semantic Event de escrita. Para `subjectType: User`, isso já acontece hoje sem nenhuma mudança — o evento `identity.created` do Identity Engine já cumpre esse papel desde a Release 3.

O desenho exato do evento de "leitura" para `Client`/`Project`/`Company` (nome, payload) é decisão da Release 8, quando `GestorSkill` for modelada — esta ADR só fixa a exigência de que ele precisa existir.

## Consequências

- Unifica completamente o mecanismo de atualização de todo `DigitalTwin` — um só caminho (`Evento → Projection → Twin`), sem bifurcação entre "primeira vez" e "atualizações seguintes". Simplifica a Implementation da Release 4B (um único handler de projeção, não dois).
- Toda leitura de sistema externo, mesmo a primeira, passa a produzir um evento no Event Bus — isso amplia levemente o escopo de toda Capability de leitura futura (ela precisa publicar, não só retornar um valor síncrono), uma restrição nova a documentar quando o Skill Engine (Release 8) for modelado.
- Reforça a auditabilidade já citada em [DIGITAL_TWIN.md](../../DIGITAL_TWIN.md) — "o estado do Twin em qualquer momento no passado é reconstruível a partir do histórico de eventos" passa a valer sem exceção nenhuma, inclusive para o estado inicial.
- Não muda nada da Release 4A/4B em si — `UserTwin` já seguia este padrão desde [ADR-0079](0079-usertwin-desde-a-release-4.md); esta ADR só remove a exceção que existia para os Twins futuros da Release 8.
