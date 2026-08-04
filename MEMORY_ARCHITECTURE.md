# Memory Architecture

## Por que Memory é o maior ativo do SIGMA

Um provedor de IA pode ser trocado sem reescrever o domínio (ver [ADR-0004](docs/adr/0004-tres-camadas-ia-agente-skill.md)). Uma Skill pode ser substituída (ver [ADR-0006](docs/adr/0006-integracao-externa-e-sempre-uma-skill.md)). O que **não** é trivialmente substituível é o que o SIGMA aprendeu ao longo do tempo sobre como a Alfa opera — isso é acumulado, não comprado. Um Memory Engine raso reduz o SIGMA a um orquestrador sem histórico, que resolve cada Mission do zero. Ver [MANIFESTO.md](MANIFESTO.md): "SIGMA registra conhecimento."

## Três níveis

```
Operational Memory   (minutos a horas — o que está acontecendo agora)
        ↓ ao concluir uma Mission, o que for relevante é extraído
Project Memory        (semanas a meses — o que se aprendeu sobre um Workspace específico)
        ↓ quando um padrão se repete entre Workspaces/Projects
Long Term Memory        (indefinido — o que a empresa nunca deveria esquecer)
```

| Nível | Escopo | Vida útil | Exemplo |
|---|---|---|---|
| **Operational Memory** | Uma Mission em execução agora | Efêmera — descartada ou promovida ao final da Mission | "Nesta Mission, o cliente já confirmou o orçamento por e-mail às 14h" |
| **Project Memory** | Um Workspace específico (ver [WORKSPACES.md](WORKSPACES.md)) | Persistente enquanto o Workspace for relevante | "O Cliente Brenno sempre pede desconto na primeira proposta — não é sinal de recusa" |
| **Long Term Memory** | Toda a organização, cross-Workspace | Indefinida, revisada raramente | "Propostas de obra sem visita técnica prévia têm taxa de retrabalho 3x maior" |

Knowledge (ver [DOMAIN.md](DOMAIN.md)) é predominantemente Long Term — factual, curado, alimentado também por [/knowledge](knowledge/). Memory é experiencial e atravessa os três níveis conforme é consolidada.

## Promoção entre níveis

Nada é promovido automaticamente de Operational para Long Term sem passar por Project — um fato observado uma única vez numa Mission não vira conhecimento institucional. A promoção depende de repetição (o mesmo padrão aparece em mais de uma Mission dentro do mesmo Workspace) ou de generalização (o mesmo padrão aparece em Workspaces diferentes). A mecânica exata — chave estável (`subjectKey`), quando repetição/generalização disparam a promoção, e como a proveniência é preservada — está formalizada em [MEMORY_MODEL.md](MEMORY_MODEL.md) e [ADR-0081](docs/adr/0081-mecanica-de-promocao-de-memory.md).

## Por que separar em três níveis

Sem essa separação, contexto operacional de uma Mission específica (que pode estar errado, incompleto, ou só fazer sentido naquele momento) se mistura com conhecimento institucional validado — um Agent poderia "aprender" algo de uma única interação ruim e tratar como verdade estabelecida. Os três níveis existem para que o SIGMA aprenda continuamente sem contaminar Long Term Memory com ruído operacional.

## Onde vive

Implementado por [packages/memory-engine](packages/memory-engine/) — Release 4 do [ROADMAP.md](ROADMAP.md). A promoção entre níveis, quando automatizada, roda via `services/scheduler`.
