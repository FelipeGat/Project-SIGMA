# Kernel

O Kernel é a fundação sobre a qual todo Engine do SIGMA roda (ver [ARCHITECTURE.md §2](docs/architecture/ARCHITECTURE.md) e [ADR-0011](docs/adr/0011-arquitetura-em-camadas-de-engines.md)). Este documento existe para responder, sem ambiguidade, uma pergunta que toda arquitetura em camadas eventualmente enfrenta: **onde termina o Kernel e começa um Engine específico?**

## O que pertence ao Kernel

- **Bootstrap da aplicação** — carregar configuração por ambiente, inicializar o contêiner de dependências, orquestrar a ordem de inicialização dos Engines.
- **Registro e ciclo de vida dos Engines** — o Kernel sabe que Intent Engine, Planner Engine, Mission Engine etc. existem e os inicializa, mas não conhece a lógica de negócio de nenhum deles.
- **Carregamento do Plugin System** — descobrir e registrar Plugins a partir de seus manifests (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)); o Kernel carrega, nunca implementa.
- **Event Bus** — o mecanismo técnico de publicação/assinatura de eventos (ver [EVENT_MODEL.md](EVENT_MODEL.md)); o *conteúdo* de um evento de domínio pertence ao Engine que o publica, não ao Kernel.
- **Health-check e status da plataforma** — se o sistema está de pé, se cada Engine e Plugin crítico está respondendo.
- **Contexto de execução transversal** — Tenant, Workspace e User atuais (ver [MULTITENANCY.md](MULTITENANCY.md) e [WORKSPACES.md](WORKSPACES.md)), disponíveis para qualquer Engine sem cada um precisar resolvê-los de novo.
- **Bootstrap de Telemetry** — inicializar logging estruturado, métricas e tracing (ver [TELEMETRY.md](TELEMETRY.md)); o *conteúdo* logado é de cada Engine.
- **Configuração e segredos** — carregamento e injeção; o *valor de negócio* de uma configuração (ex: política de precificação) nunca vive no Kernel.

## O que nunca pertence ao Kernel

- Qualquer regra de negócio de domínio — isso é sempre de um Engine específico.
- Interpretação de linguagem natural — Intent Engine.
- Decisão de plano de execução — Planner Engine.
- Implementação concreta de qualquer Plugin — o Kernel só conhece o manifest, nunca o código do Plugin.
- Persistência de Mission, Knowledge, Memory — cada uma pertence ao Engine dono daquele domínio.
- Apresentação/UI — isso é de `apps/*`.
- Decisão de negócio de qualquer tipo (precificação, aprovação de proposta, escopo de contrato) — o Kernel não tem opinião de negócio, só orquestra quem tem.

## Teste prático

Uma peça de lógica pertence ao Kernel se, e somente se, remover essa peça quebra **todos** os Engines igualmente (ex: sem bootstrap, nenhum Engine inicializa). Se remover a peça só afeta um Engine específico, ela pertence a esse Engine — mesmo que pareça "genérica" o suficiente para viver no núcleo.

## Onde vive

Implementação em [packages/kernel](packages/kernel/) — Release 2 do [ROADMAP.md](ROADMAP.md), pré-requisito de toda camada seguinte.
