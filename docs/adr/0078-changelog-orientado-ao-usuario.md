# ADR-0078: `CHANGELOG.md` orientado ao usuário, distinto de toda documentação técnica

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Toda documentação do SIGMA até aqui — ADRs, Decision Logs, Validation Reports, Proposals — é escrita para quem constrói o sistema, não para quem vai usá-lo. O Product Owner observou uma virada de fase: "Até hoje desenvolvemos Release. Agora começamos a desenvolver Produto" — a partir daqui, o que o SIGMA já faz de fato precisa de um registro legível por alguém que não sabe o que é um Aggregate ou um Event Bus.

## Decisão

`CHANGELOG.md` (raiz do repositório) registra, por Release, o que o SIGMA passou a **fazer** — nunca como foi construído. Formato: nome da Release, lista do que ficou disponível, em linguagem de produto ("✔ Login", não "implementado `Authenticate::execute()`"). Escrito retroativamente até a Release 3 nesta ADR; a partir daqui, toda Release que entrega capacidade nova ao usuário final ganha uma entrada aqui, junto do Decision Log e do Validation Report (que continuam existindo, para público diferente).

Releases de infraestrutura pura sem nada observável pelo usuário (e Releases de consolidação, como esta 3.5) podem não gerar entrada nova, ou gerar uma entrada breve reconhecendo que a base ficou mais sólida, sem prometer capacidade que não existe.

## Consequências

- Quando o SIGMA começar a ser usado no dia a dia (a expectativa do próprio Product Owner), existe um documento único para responder "o que ele já faz" sem precisar ler ADRs.
- Reforça, na prática, a virada de "Release" para "Produto" que o Product Owner sinalizou — o primeiro artefato do projeto pensado inteiramente para quem não é o time de desenvolvimento.
- Não substitui nenhum documento técnico existente — Decision Log continua sendo "por que decidimos X", Validation Report continua sendo "prova de que funciona", CHANGELOG.md é só "o que passou a existir".
