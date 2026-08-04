# ADR-0090: Aprovação é um estado de primeira classe da Mission, distinto do gate por Capability

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva) já define Autonomia Progressiva e um mecanismo de confirmação humana — `nextActions` no Envelope, quando o nível efetivo de uma chamada exige confirmação. Esse mecanismo é **por chamada de Capability**, resolvido a cada invocação. O Product Owner pediu que Mission modelasse "aprovação" como parte do seu próprio ciclo de vida (ver [ROADMAP.md](../../ROADMAP.md#release-5--mission-engine)) — não estava claro se isso deveria reaproveitar o mecanismo existente ou ser algo novo.

## Decisão

Aprovação passa a existir em dois níveis complementares, nunca um substituindo o outro:

1. **Por Capability** (já existente, [SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva)) — continua resolvendo, a cada chamada, se aquela Capability específica pode executar sem confirmação.
2. **Por Mission** (novo, este ADR) — um `ApprovalGate` pendente (`MissionStatus: PendingApproval`) bloqueia **toda** a Mission, não só a próxima chamada — nenhuma nova Subtask começa até a decisão. Ver [MISSION_MODEL.md — ApprovalGate](../../MISSION_MODEL.md#approvalgate) e [MISSION_LIFECYCLE.md — Fluxo 1/2](../../MISSION_LIFECYCLE.md).

Um `ApprovalGate` nasce quando o `autonomyCeiling` da Mission (resolvido uma vez, na criação, a partir do User/Role que a originou) não é suficiente para a próxima Subtask — o mesmo `min(nível do ator, nível exigido)` já usado por Capability, aplicado agora também no nível da Mission como um todo.

## Consequências

- Simplicidade deliberada: quando uma Mission está `PendingApproval`, ela pausa por inteiro — nenhuma Subtask paralela continua, mesmo que não precisasse do mesmo gate. Reduz a complexidade de estado, ao custo de não paralelizar Subtasks independentes enquanto uma aprovação está pendente (aceito conscientemente; paralelismo de Subtask não é escopo desta Release).
- `nextActions` do Envelope continua sendo o canal de superfície para ambos os níveis de aprovação — a distinção entre "esta chamada precisa de confirmação" e "esta Mission inteira está pausada" é responsabilidade de quem consome o Envelope (Interfaces, Release 13), não deste ADR.
- Reforça a lacuna já sinalizada em `ROADMAP.md` (componente estrutural **Policy Engine**, sem Release própria): parte do que um Policy Engine centralizaria no futuro está sendo modelado agora, dentro de Mission, porque não há alternativa disponível ainda. Quando o Policy Engine existir, é esperado que absorva parte desta responsabilidade — mudança futura sinalizada, não implementada aqui.
