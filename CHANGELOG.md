# Changelog

O que o SIGMA passou a fazer, Release a Release — escrito para quem vai usar o sistema, não para quem o constrói. Documentação técnica (por quê, como foi validado) fica em `docs/releases/*-decision-log.md` e `*-validation-report.md`; aqui só o que mudou na prática. Ver [ADR-0078](docs/adr/0078-changelog-orientado-ao-usuario.md).

## Release 6 — Planner

**Agora o SIGMA decide sozinho o que fazer.** Até aqui, quem pedia tinha que escrever os passos; nesta Release, descreve-se o objetivo e o sistema monta o plano:

> *"Iniciar a implantação do AlfaGym para a Sea Master"*

vira, sozinho, uma Missão com as quatro etapas do processo da Alfa — na ordem certa, e com a etapa de ativação em produção marcada para exigir aprovação de uma pessoa, exatamente como o Playbook manda.

- ✔ Sete tipos de pedido reconhecidos, um para cada Playbook já documentado (nova reunião, novo cliente, novo orçamento, nova obra, nova implantação, nova academia, novo condomínio)
- ✔ Quando o SIGMA não sabe planejar, ele **diz que não sabe** em vez de inventar um plano genérico — e o aviso aponta qual Playbook está faltando
- ✔ Quem decide é o sistema, com as regras da Alfa. Nenhuma IA decide o que vai ser feito

Ainda é `curl` no terminal, e a Missão criada **não executa sozinha** — quem faz cada etapa acontecer (os Agentes e as integrações) chega nas Releases 8 a 10. Ver [docs/releases/0006b-planner-implementation.md](docs/releases/0006b-planner-implementation.md).

## Release 5.6 — Observability Baseline

Nenhuma capacidade nova para quem usa o sistema — esta Release fez o SIGMA **reagir** ao próprio estado de saúde, em vez de apenas relatá-lo:

- ✔ Se o banco cai, os serviços saem do tráfego automaticamente, sem que ninguém precise perceber
- ✔ Quando o banco volta, eles voltam sozinhos — medido em 4 segundos
- ✔ Um serviço com problema não é mais reiniciado à toa: o sistema distingue "travado" (reinicia) de "esperando uma dependência" (só espera)
- ✔ O `auth` passou a informar seu estado, coisa que nunca fez desde que entrou no ar

Ver [docs/releases/0005.6-observability-baseline.md](docs/releases/0005.6-observability-baseline.md).

## Release 5.5 — Vertical Slice

**Pela primeira vez, o SIGMA faz algo de ponta a ponta.** Até aqui cada Release entregava uma camada interna; esta ligou todas elas num caminho que uma pessoa consegue percorrer:

- ✔ Criar o primeiro usuário do sistema — antes disso, não existia nenhuma forma de criar um Tenant, uma empresa ou um Workspace sem mexer no banco à mão
- ✔ Fazer login, escolher o Workspace, **registrar uma Missão** e consultá-la depois
- ✔ Cada Missão fica isolada por empresa: uma Missão de um cliente é invisível para outro, mesmo com login válido
- ✔ O sistema se recupera sozinho quando o Redis ou o banco piscam, em vez de ficar fora do ar até alguém reiniciar na mão

Ainda é `curl` no terminal, não uma tela — e uma Missão registrada **fica parada**, porque quem a faz avançar (Planner, Agentes, Skills) chega nas Releases seguintes. O ganho aqui é de confiança: as cinco Releases anteriores foram provadas funcionando juntas, não só isoladamente. Ver [docs/releases/0005.5-vertical-slice.md](docs/releases/0005.5-vertical-slice.md).

## Release 4 — Memory

Agora o SIGMA consegue:

- ✔ Guardar um "retrato" sempre atualizado de cada pessoa (`UserTwin`) — sincronizado automaticamente assim que alguém faz login e escolhe um Workspace, sem nenhuma ação manual
- ✔ Registrar um fato observado (`MemoryRecord`) e promovê-lo a um padrão confiável quando ele se repete o suficiente, com um nível de confiança explícito
- ✔ Indexar e versionar o conteúdo de `/knowledge` — nunca perder uma versão anterior quando o conteúdo muda
- ✔ Sinalizar quando algo aprendido parece durável o bastante para virar conhecimento institucional — sem nunca decidir isso sozinho, sempre com um humano confirmando

Ainda sem nenhuma tela nem API pública consumindo isso — a mesma disciplina da Release 3: primeiro a base certa, depois a interface. Ver [docs/releases/0004a-memory-domain.md](docs/releases/0004a-memory-domain.md) e [docs/releases/0004b-memory-infrastructure.md](docs/releases/0004b-memory-infrastructure.md).

## Release 3.5 — Architecture Consolidation

Nenhuma capacidade nova para o usuário final — esta Release fortaleceu a base (documentação, nomenclatura, testes) antes da próxima fase do projeto. Ver [docs/releases/0003.5-architecture-consolidation.md](docs/releases/0003.5-architecture-consolidation.md).

## Release 3 — Identity

Agora o SIGMA consegue:

- ✔ Criar uma Identity (uma pessoa com acesso ao sistema)
- ✔ Login com e-mail e senha
- ✔ Selecionar em qual Workspace (contexto de trabalho — ex: um cliente específico) operar
- ✔ Saber, a qualquer momento, quais permissões e qual nível de autonomia uma pessoa tem naquele Workspace
- ✔ Logout

Isso ainda não está acessível por nenhuma tela — é a base sobre a qual toda funcionalidade futura vai se apoiar. Vai ficar útil no dia a dia a partir do momento em que houver uma interface (Release 13) consumindo essa base.

## Release 2 — Bootstrap

O SIGMA passou a existir como processo executável: sobe, verifica se está saudável (`/health/live`, `/health/ready`, `/health/startup`), e sabe descrever o que tem carregado. Ainda sem nenhuma funcionalidade de negócio — só a fundação técnica que sustenta tudo que veio depois.

## Release 1 — Protocol

Definição de como toda parte do SIGMA se comunica — nenhuma mudança visível ainda, mas todo o resto do projeto passou a falar a mesma língua a partir daqui.

## Release 0 — Foundation

Visão, arquitetura e processo de trabalho do projeto definidos. Nenhum código ainda.
