# Mission Manifesto

## Por que Mission é diferente de tudo que o SIGMA já construiu

Identity responde "quem". Memory responde "o que sei, o que aprendi". Nenhuma das duas **decide** nada — guardam, autenticam, sincronizam, mas o julgamento de o que fazer nunca esteve nelas. A partir da Release 5, isso muda: **Mission é a primeira Engine que faz o SIGMA decidir** — "o que deve acontecer, quem executa, quando, em qual ordem, com quais permissões" (avaliação do Product Owner, registrada em [ROADMAP.md](ROADMAP.md#release-5--mission-engine)). Até aqui o SIGMA era, com toda a razão, um framework excelente. É na Mission que ele começa a se tornar um colaborador digital de verdade — a peça que, um dia, entende "participe da reunião com o cliente X, registre isso no Gestor, atualize o funil, altere o orçamento e saiba exatamente o que fazer" e a transforma em trabalho real, rastreável, correto.

Isso já estava anunciado desde a Foundation — [ADR-0003](docs/adr/0003-mission-como-entidade-central.md) chamou Mission de "o agregado raiz do sistema" antes de qualquer linha de código existir. O que muda agora não é a centralidade (ela sempre foi verdadeira), é o que passa a ser exigido dela: não basta acompanhar Subtasks até a conclusão — Mission precisa carregar **ciclo de vida, eventos, estado, histórico, autonomia, aprovação, retries, compensações, correlação**. A "unidade de trabalho" do SIGMA, não um registro passivo do que outros Engines fizeram.

## O que já estava decidido, e que este documento preserva

- **Mission é o agregado raiz** ([ADR-0003](docs/adr/0003-mission-como-entidade-central.md)) — toda funcionalidade nova se posiciona no ciclo de vida de uma Mission antes de ser aceita; domínios de suporte (Knowledge, Memory, Skill, Agent, Automation) existem para servi-la, nunca como módulos independentes.
- **Uma Intent pode gerar várias Missions** ([ADR-0028](docs/adr/0028-intencao-nao-comando.md)) — cardinalidade 1:N, todas rastreáveis à mesma Intent de origem via `correlationId` ([SIGMA_PROTOCOL.md §1](SIGMA_PROTOCOL.md#1-o-envelope)).
- **Mission nunca decide sozinha o Plan** — quem decompõe uma Intent em Subtasks candidatas é o Planner Engine ([ADR-0012](docs/adr/0012-planner-decide-nunca-a-ia.md)); Mission Engine recebe um Plan já pronto e o executa, gerencia, e responde por ele.
- **Mission é construída antes do Planner existir** ([ADR-0031](docs/adr/0031-ordem-runtime-vs-desenvolvimento.md), reconfirmado pelo Product Owner em 2026-08-04, sem reabrir) — Release 5 antes da Release 6. Um Plan mockado/manual é a entrada desta Release; a Ordem de Runtime (`Intent → Planner → Mission → Execution`) nunca muda, só a ordem em que o código nasce.
- **Toda escrita real do SIGMA se correlaciona a uma Mission** — todo documento em [/skills](skills/) repete a mesma regra: nenhuma Capability de escrita executa sem uma Mission de origem rastreável. Mission é o que autoriza e atribui uma ação, não só o que a registra depois.

## O que este Manifesto acrescenta — a parte nova, pedida explicitamente

O esboço de ciclo de vida já existente em [ARCHITECTURE.md §6](docs/architecture/ARCHITECTURE.md) (escrito na Foundation, antes de qualquer Engine real existir) tem um estado para "falhou" e um loop simples de retry, mas nenhum estado para "aguardando aprovação humana antes de prosseguir" e nenhum caminho para "algo já foi feito, precisa ser desfeito". [MEMORY_MODEL.md](MEMORY_MODEL.md)/[IDENTITY_MODEL.md](IDENTITY_MODEL.md) tiveram o luxo de modelar domínios essencialmente estáveis (uma Identity não "falha parcialmente", um MemoryRecord não precisa ser "compensado"). Mission é diferente: ela orquestra ações reais, algumas irreversíveis, em sistemas externos — e pode falhar no meio do caminho, com efeitos colaterais já produzidos.

Por isso [MISSION_MODEL.md](MISSION_MODEL.md) e [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md) formalizam, pela primeira vez no projeto:

1. **Aprovação como estado de primeira classe** — não apenas uma checagem de Autonomia Progressiva por Capability ([SIGMA_PROTOCOL.md §5](SIGMA_PROTOCOL.md#5-autonomia-progressiva), que continua existindo e vale por chamada), mas um estado da própria Mission (`PendingApproval`) que bloqueia todo o seu progresso até uma decisão humana.
2. **Retry como histórico de Subtask, não como estado da Mission** — uma Mission em progresso continua "em progresso" enquanto uma Subtask tenta de novo dentro da política permitida; só quando as tentativas se esgotam é que a Mission muda de fato de estado.
3. **Compensação como estado de primeira classe** — quando uma Subtask falha definitivamente depois de já ter produzido efeito em algum sistema externo, a Mission entra num estado próprio (`Compensating`) para desfazer ou sinalizar esse efeito, antes de poder ser considerada encerrada.
4. **Histórico e correlação como parte do modelo, não um efeito colateral de Log** — toda transição de estado de uma Mission é, ela mesma, um evento nomeado e catalogado (ver [MISSION_EVENTS.md](MISSION_EVENTS.md)), correlacionável pelo mesmo `correlationId` que atravessa toda a cadeia Intent→Missions.

## O que este Manifesto não decide

Números exatos (quantas tentativas antes de desistir, que tipo de compensação para cada tipo de Capability, timeout de uma aprovação pendente) são decisão de Implementation, respondidos com precisão em [MISSION_MODEL.md](MISSION_MODEL.md#o-que-este-modelo-não-decide) e [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md), não aqui. A unificação de "pode? até quanto? precisa aprovação?" num Policy Engine central — hoje espalhado entre Autonomia Progressiva e Permission, e agora também parcialmente em Mission — é um componente estrutural já sinalizado pelo Product Owner ([ROADMAP.md](ROADMAP.md), tabela de componentes), sem Release própria ainda; o que Mission modela agora é o suficiente para funcionar, sabendo conscientemente que uma futura unificação pode reabsorver parte dessa responsabilidade.

## Onde vive

Fundação em `packages/mission-engine` — Release 5 do [ROADMAP.md](ROADMAP.md), seguindo o [Processo Oficial de Desenvolvimento de Engines do SIGMA](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) ([ADR-0082](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md)): este Manifesto, [MISSION_MODEL.md](MISSION_MODEL.md), [MISSION_LIFECYCLE.md](MISSION_LIFECYCLE.md), [MISSION_EVENTS.md](MISSION_EVENTS.md) e `contracts/Mission.contract.yaml` precisam estar aprovados antes de qualquer código.
