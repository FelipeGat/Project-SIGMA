# ADR-0081: Mecânica de promoção de Memory — repetição dentro do Workspace, generalização entre Workspaces

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[ADR-0022](0022-memory-em-tres-niveis.md) definiu os três níveis de Memory (Operational/Project/Long Term) e a regra de que promoção "depende de repetição... ou de generalização", mas deixou a mecânica exata explicitamente para o épico do Memory Engine — "não antecipada aqui". [MEMORY_MODEL.md](../../MEMORY_MODEL.md) precisava dessa mecânica definida para poder modelar `MemoryRecord`.

## Decisão

Todo `MemoryRecord` carrega um `subjectKey` — uma chave estável identificando do que o registro trata (ex: `client.brenno.discount-behavior`). A promoção usa essa chave:

1. **Operational → Project**: o mesmo `subjectKey`, dentro do mesmo `workspaceId`, associado a mais de uma `missionId` — repetição dentro do mesmo contexto.
2. **Project → LongTerm**: o mesmo `subjectKey` (ou sua forma generalizada — ex: `client.*.discount-behavior`) presente como Memory `Project` em mais de um `workspaceId` — o padrão deixa de ser sobre uma situação específica e passa a ser sobre um comportamento de negócio.

Uma promoção sempre cria um novo `MemoryRecord` no nível de destino, com `promotedFrom` apontando para o(s) registro(s) de origem — o registro original nunca é apagado nem sobrescrito. A avaliação (`EvaluatePromotion`) é uma operação explícita, chamável sob demanda na Release 4 — automação por agendamento depende do componente estrutural Scheduler, ainda sem Release própria ([ROADMAP.md](../../ROADMAP.md)).

## Consequências

- A regra de [ADR-0022](0022-memory-em-tres-niveis.md) ("nunca automática de Operational direto para Long Term") continua valendo — a mecânica aqui só formaliza como "repetição"/"generalização" são detectadas, não muda a regra em si.
- Todo `MemoryRecord` promovido mantém proveniência completa (`promotedFrom`) — uma dúvida futura como "por que o SIGMA acha que isso é verdade?" sempre tem resposta rastreável até a(s) Mission(ões) de origem.
- `subjectKey` generalizado (`client.*.discount-behavior`) exige alguma normalização de chave — a implementação exata desse mecanismo (regras de generalização automática vs. curadoria manual da forma generalizada) é decisão de Implementation da Release 4, não desta ADR.
- Sem o componente Scheduler, a promoção não acontece sozinha — alguém (ou algum outro processo) precisa chamar `EvaluatePromotion`. Aceito conscientemente como limitação da Release 4; revisitar quando o Scheduler ganhar Release própria.
