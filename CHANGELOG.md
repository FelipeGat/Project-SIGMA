# Changelog

O que o SIGMA passou a fazer, Release a Release — escrito para quem vai usar o sistema, não para quem o constrói. Documentação técnica (por quê, como foi validado) fica em `docs/releases/*-decision-log.md` e `*-validation-report.md`; aqui só o que mudou na prática. Ver [ADR-0078](docs/adr/0078-changelog-orientado-ao-usuario.md).

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
