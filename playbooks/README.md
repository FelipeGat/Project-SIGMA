# Playbooks

Um Playbook é a forma documentada de um tipo de Mission recorrente — o rascunho, ainda em linguagem humana, do que mais tarde vira um `Process` executável (ver [DOMAIN.md](../DOMAIN.md)). Antes de o Planner Engine existir, é aqui que se registra como o Planner *deveria* pensar diante de um pedido como "Sigma, participe da reunião do cliente Brenno" ou "Sigma, abra a implantação do cliente X".

Diferença em relação a `knowledge/processos/`: lá descreve-se o processo como a Alfa pratica hoje; aqui já se escreve pensando em como o SIGMA vai planejar e executar — é o elo entre o conhecimento de negócio e a arquitetura de Missions.

## Como usar

Todo novo Playbook segue [template.md](template.md). Um Playbook não precisa estar completo para existir — o objetivo do Sprint 0.1 é ter o esqueleto certo, populado por quem conhece o processo, incrementalmente, até que o Épico correspondente do Planner/Mission Engine possa consumi-lo.

| Playbook | Cenário |
|---|---|
| [nova-reuniao.md](nova-reuniao.md) | "Sigma, participe da reunião do cliente X" |
| [novo-cliente.md](novo-cliente.md) | Entrada de um novo cliente no ecossistema Alfa |
| [novo-orcamento.md](novo-orcamento.md) | Elaboração de uma nova proposta comercial |
| [nova-obra.md](nova-obra.md) | Abertura e acompanhamento de uma obra |
| [nova-implantacao.md](nova-implantacao.md) | Implantação de um sistema (Gestor.Alfa, AlfaControl, AlfaGym...) para um novo cliente |
| [nova-academia.md](nova-academia.md) | Onboarding de uma academia como cliente AlfaGym |
| [novo-condominio.md](novo-condominio.md) | Onboarding de um condomínio como cliente AlfaControl |
