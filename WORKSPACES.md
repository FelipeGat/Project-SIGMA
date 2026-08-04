# Workspaces

## O problema que resolve

O SIGMA não trabalha em "um Projeto". Ele trabalha em um **contexto** — tudo que está relacionado a uma situação de negócio específica, ao mesmo tempo. Quando alguém entra no contexto "Cliente Brenno", o SIGMA já deveria saber, sem precisar buscar de novo: a Company, os Projects em andamento, os Budgets abertos, o histórico de e-mails e reuniões, os chamados, os arquivos relevantes, a conversa de WhatsApp recente. Essa unidade de contexto é o **Workspace**.

## Workspace não é Project

`Project`, em [DOMAIN.md](DOMAIN.md), é uma entidade de negócio — um projeto/obra específico, referenciado via Skill a partir do Gestor.Alfa. `Workspace` é uma camada acima: agrupa tudo que é relevante para uma situação (tipicamente um Client, mas pode ser uma iniciativa interna sem cliente externo), incluindo múltiplos Projects, Budgets, Meetings e Documents relacionados.

```
Workspace: "Cliente Brenno"
├── Company / Client de origem
├── Projects em andamento
├── Budgets (abertos e históricos)
├── Meetings (passadas e agendadas)
├── Documents (propostas, atas, contratos)
└── Histórico de comunicação (e-mail, WhatsApp, Telegram)
```

## Como um Workspace se relaciona com uma Mission

Uma Mission tipicamente executa **dentro** de um Workspace. Quando o Intent Engine interpreta "Sigma, participe da reunião do cliente Brenno" dentro do Workspace "Cliente Brenno" já ativo, ele não precisa desambiguar qual cliente — o contexto já resolve isso. Isso é resolvido pelo [Kernel](KERNEL.md) como parte do contexto de execução transversal (ver também [MULTITENANCY.md](MULTITENANCY.md)), disponível a qualquer Engine sem cada um perguntar de novo.

## Como um Workspace é populado

Um Workspace não duplica dados — ele agrega, via Skill, o que já existe nos sistemas de origem (Gestor.Alfa para Client/Project/Budget, Google Calendar para Meeting, etc. — ver [/skills](skills/)). O SIGMA não é dono desses dados; é dono da visão agregada e do histórico de Missions executadas dentro daquele contexto.

## Quem pode ter um Workspace

Um Workspace pertence a uma Company dentro de um Tenant (ver [MULTITENANCY.md](MULTITENANCY.md)) e é visível a Users/Teams autorizados. Um User pode alternar entre múltiplos Workspaces conforme seu escopo de acesso.

## Onde vive

Não tem Engine dedicado próprio — é modelado como parte do schema de multiempresa do **Identity Engine** (Release 3, ver [MULTITENANCY.md](MULTITENANCY.md) e [ADR-0039](docs/adr/0039-identity-engine.md)) e materializado a partir de dados agregados via Skills/Digital Twins, disponibilizado pelo [Kernel](KERNEL.md) como contexto de execução a qualquer Engine.
