# ADR-0047: Toda Release produz Código e Decision Log

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

ADRs registram decisões arquiteturais de escopo amplo, revisadas raramente. Faltava um artefato de granularidade menor, por Release: por que uma escolha específica de implementação foi feita dentro do escopo já aprovado, quais alternativas foram descartadas e por quê, e qual impacto era esperado — informação que se perde se só existir na memória de quem implementou, e que não justifica, sozinha, uma ADR nova a cada decisão local.

## Decisão

A partir de agora, toda Release que produz código produz **dois artefatos**: o Código em si (quando aprovado) e um **Decision Log** — documento em `docs/releases/000N-<nome>-decision-log.md`, escrito durante/ao final da implementação, registrando decisões locais tomadas dentro do escopo já aprovado, alternativas descartadas e o porquê, e o impacto esperado. Convenção mantida em [docs/releases/README.md](../../docs/releases/README.md).

## Consequências

- Quando o Project SIGMA tiver centenas de milhares de linhas de código, decisões locais tomadas anos antes continuam explicáveis sem depender da memória de quem participou.
- Diferença clara de propósito em relação a ADR: um Decision Log é escrito *durante* a implementação de uma Release já aprovada (explica escolhas dentro do escopo definido); uma ADR é escrita *antes*, para decisões que afetam mais de um módulo ou mudam um contrato já estabelecido. Uma decisão de Decision Log que se mostra maior do que o esperado (afeta outro Engine, muda um contrato) é promovida a ADR — não fica só no Decision Log.
- Nenhuma Release é considerada "concluída" sem os dois artefatos — Código sem Decision Log é aceito apenas como intermediário, não como entrega final.
- A proposta formal de cada Release (formato de [ADR-0010](0010-processo-por-epicos-com-aprovacao.md)) já existe antes do código; o Decision Log é seu complemento natural, escrito depois.
