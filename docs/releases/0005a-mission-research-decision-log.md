# Release 5A — Mission Research — Decision Log

Decisões locais tomadas durante a pesquisa e modelagem, dentro do escopo já aprovado em [0005a-mission-research.md](0005a-mission-research.md) (revisão 1). Ver [ADR-0047](../adr/0047-decision-log-por-release.md) para o que este documento é (e não é).

## O que foi feito

Pesquisa dedicada (agente de exploração) sobre toda menção existente a "Mission" no repositório — `DOMAIN.md`, `ARCHITECTURE.md §5-6`, `SIGMA_PROTOCOL.md`, `EVENT_MODEL.md`, ADR-0003/0028/0031, `WORKSPACES.md`, `MULTITENANCY.md`, playbooks, skills, agents — antes de escrever qualquer documento de modelo, mesmo padrão já usado antes da Release 4A (Memory). A pesquisa encontrou uma contradição estrutural real e duas divergências cosméticas — ambas tratadas explicitamente, nenhuma decidida em silêncio.

## Achado real: `mission-engine`/`planner-engine` tinham dependência Composer que contradizia a Ordem de Desenvolvimento

`packages/README.md` listava `mission-engine` dependendo de `planner-engine`, e `planner-engine` dependendo de `intent-engine`. Isso contradiz diretamente [ADR-0031](../adr/0031-ordem-runtime-vs-desenvolvimento.md), reconfirmado pelo próprio Product Owner em 2026-08-04 sem reabrir: Mission (Release 5) é construída **antes** de Planner (Release 6) existir; Planner é construído **antes** de Intent (Release 7) existir — cada um contra uma entrada mockada/manual, nunca uma dependência de código real do Engine seguinte na Ordem de Runtime. Se a dependência Composer estivesse correta como estava escrita, a Release 5 seria literalmente impossível de implementar até a Release 6 terminar — o oposto do que o roadmap decide.

Corrigido nesta rodada: `packages/README.md` (as duas linhas), `packages/mission-engine/README.md`, e formalizado como decisão permanente em [ADR-0092](../adr/0092-plan-e-conceito-proprio-do-mission-engine.md) — `Plan`/`Subtask` candidata são Value Objects do próprio `packages/mission-engine`, nunca importados de `planner-engine`.

## Duas divergências cosméticas encontradas, sinalizadas — não corrigidas retroativamente em ADRs

- `docs/adr/0003-mission-como-entidade-central.md` cita o ciclo de vida como estando em `ARCHITECTURE.md §5`; hoje está em `§6` (§5 é "Modelo de domínio"). Não corrigido no texto da ADR-0003 — ADRs não são revogadas por edição, mesmo para uma referência factual desatualizada (mesmo princípio já aplicado a `IDENTITY_MODEL.md` na Release 3.5). Sinalizado em [ADR-0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md) e aqui.
- `DOMAIN.md` citava `SIGMA_PROTOCOL.md#4-autonomia-progressiva`; a seção correta é `#5`. `DOMAIN.md` **não é uma ADR** — é um glossário vivo, editável — corrigido diretamente nesta rodada.

## Decisão real: onde o domínio de Mission começa

`ARCHITECTURE.md §6` mistura, no mesmo diagrama, estágios que hoje sabemos pertencer a Intent/Planner Engine (`Recebida`/`Interpretando`/`Planejando`/`Rejeitada`) com o que é de fato o Mission Engine (`SubtarefasCriadas` em diante). [MISSION_MODEL.md](../../MISSION_MODEL.md)/[MISSION_LIFECYCLE.md](../../MISSION_LIFECYCLE.md) resolvem isso explicitamente: o Aggregate `Mission` só existe a partir de um `Plan` aceito — nunca antes. Formalizado em [ADR-0089](../adr/0089-mission-nasce-do-plan-nao-da-intent.md). `ARCHITECTURE.md §6` não foi reescrito (continua útil como visão fim-a-fim cross-Engine); `MISSION_LIFECYCLE.md` é que passa a ser a fonte de verdade específica do ciclo de vida do Aggregate.

## Decisão real: reconciliar três vocabulários pré-existentes do mesmo fluxo

`ADR-0003` (prosa, 9 passos), `ARCHITECTURE.md §6` (diagrama, 11 estados) e `EVENT_MODEL.md` (12 eventos canônicos) descreviam o mesmo fluxo com granularidades e nomes diferentes, nunca normalizados. `MISSION_LIFECYCLE.md` é, deliberadamente, o documento que assume esse papel de reconciliação — mapeando cada evento/estado dos três para o `MissionStatus` final adotado, em vez de inventar um quarto vocabulário isolado.

## Decisão real: aprovação, retry e compensação — as três exigências novas do Product Owner

Nenhum documento existente definia a mecânica de nenhum dos três antes desta rodada — greenfield completo. Resolvido com três decisões deliberadas, cada uma com seu ADR:

- **Aprovação é um estado de primeira classe da `Mission`** (`PendingApproval`), complementar — nunca substituto — do gate por Capability já existente em `SIGMA_PROTOCOL.md §5`. [ADR-0090](../adr/0090-aprovacao-como-estado-de-primeira-classe.md).
- **Retry é histórico da `Subtask`**, nunca um `MissionStatus` — a Mission continua `InProgress` durante as tentativas. **Compensação é estado da `Mission`** (`Compensating`), porque o que precisa ser resolvido depois de uma falha definitiva com efeito já produzido é o que a Mission inteira já fez, não só uma Subtask isolada. [ADR-0091](../adr/0091-retry-subtask-compensacao-mission.md).
- **`Mission.workspaceId` é opcional** — a maioria das Missions nasce dentro de um Workspace, mas uma Mission de sistema/manutenção não precisa simular ter um. [ADR-0093](../adr/0093-mission-workspace-opcional.md).

## Tensão sinalizada, não resolvida silenciosamente: parte do que Mission modela hoje pertence, no futuro, a um Policy Engine

`ROADMAP.md` já sinaliza um componente estrutural **Policy Engine**, sem Release própria, para unificar "pode? até quanto? precisa aprovação? executa sozinho?". O `ApprovalGate`/`autonomyCeiling` modelados nesta Release cobrem parte dessa responsabilidade, porque não há alternativa disponível agora. Isso é reconhecido explicitamente em [MISSION_MANIFESTO.md](../../MISSION_MANIFESTO.md#o-que-este-manifesto-não-decide) e [ADR-0090](../adr/0090-aprovacao-como-estado-de-primeira-classe.md) — não escondido como se fosse uma decisão definitiva e permanente.

## Impacto para a Release 5B

- `packages/mission-engine/src/Domain/` implementa exatamente as entidades e a máquina de estados já modeladas — nenhuma delas foi desenhada pensando em persistência.
- A Proposal de 5B decide se a Release se divide em sub-Releases Domain/Infrastructure (mesmo padrão de [ADR-0060](../adr/0060-release-dividida-em-sub-releases.md)) — não antecipado aqui.
- Perguntas em aberto explícitas para a Architecture Review de 5B: onde exatamente a distinção "Subtask já produziu efeito ou não" (gatilho de `Compensating`) é resolvida; número exato de tentativas de retry; timeout de um `ApprovalGate` pendente.
