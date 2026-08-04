# ADR-0059: O repositório é a fonte da verdade — "Repository is the Source of Truth"

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O trabalho de arquitetura do SIGMA até aqui foi conduzido em colaboração direta com um assistente de IA (Claude), que mantém, além do repositório, um sistema de memória próprio — persistente entre conversas, mas externo ao projeto. Esse sistema de memória acelera o trabalho (não é preciso reexplicar contexto a cada sessão), mas cria um risco estrutural se não for delimitado explicitamente: a memória de um modelo de IA pode mudar de formato, ser apagada, ficar indisponível, ou simplesmente não existir para outro modelo/ferramenta que venha a colaborar no projeto no futuro (outro assistente, outro desenvolvedor humano, uma IA diferente daqui a dois anos). Se qualquer decisão crítica do SIGMA existisse **apenas** nessa memória externa, o projeto ficaria refém de uma ferramenta específica — o oposto do que o próprio SIGMA se propõe a ser (ver [MANIFESTO.md](../../MANIFESTO.md), [VISION_2030.md](../../VISION_2030.md)).

## Decisão

Toda informação necessária para reconstruir completamente o entendimento do Project SIGMA — visão, arquitetura, decisões, estado de cada Release, processo — deve existir **dentro do próprio repositório**. Nenhuma decisão crítica depende da memória de uma IA. Qualquer memória externa (de um assistente de IA ou de qualquer outra ferramenta) é, na melhor das hipóteses, um **cache de conveniência** que acelera retomar o trabalho — nunca a fonte de verdade sobre o que o projeto é ou decidiu.

Teste prático desta regra: **se uma IA esquecer tudo amanhã, basta clonar o repositório para conseguir continuar o desenvolvimento exatamente de onde parou.** Qualquer informação cuja ausência quebraria esse teste precisa ganhar um documento, uma ADR, ou uma entrada em `memory/` (a pasta do próprio repositório, distinta de qualquer memória externa de ferramenta) — nunca ficar apenas na conversa.

## Consequências

- Uma IA nova (ou um desenvolvedor humano novo) consegue participar do projeto apenas clonando o repositório — sem precisar de acesso a nenhum histórico de conversa anterior.
- O histórico técnico pertence ao projeto, não ao modelo de IA que ajudou a produzi-lo.
- Toda memória externa de ferramenta (incluindo a memória própria do Claude usada nesta colaboração) deve ser tratada e documentada explicitamente como cache — nunca como registro autoritativo. Ver `memory/README.md`, atualizado junto desta ADR para declarar essa regra.
- Custo: alguma disciplina extra ao final de cada rodada de trabalho, garantindo que toda decisão relevante seja escrita no repositório antes de considerar a rodada concluída — já era, na prática, o hábito seguido desde a Release 0 (Decision Log, ADRs, `memory/STATE.md`/`NEXT.md`/`DECISIONS.md`); esta ADR apenas torna esse hábito um princípio explícito e permanente.
- Aumenta a longevidade da plataforma: o SIGMA não fica preso ao contexto de uma única conversa, de um único modelo de IA, ou de uma única ferramenta.
