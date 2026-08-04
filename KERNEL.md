# Kernel

O Kernel é a fundação sobre a qual todo Module do SIGMA roda — Engine, Plugin, Service ou Package (ver [ARCHITECTURE.md §2](docs/architecture/ARCHITECTURE.md), [ADR-0011](docs/adr/0011-arquitetura-em-camadas-de-engines.md) e [BOOTSTRAP.md](BOOTSTRAP.md)). Este documento existe para responder, sem ambiguidade, duas perguntas que toda arquitetura em camadas eventualmente enfrenta: **onde termina o Kernel e começa um Module específico?** e **o que o Kernel tem permissão de saber sobre o que carrega?**

## O Kernel nunca conhece Engine — só Module

Esta é a regra mais importante deste documento, formalizada em [ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md). O Kernel não importa, não referencia e não tem nenhuma lógica condicional sobre "Mission Engine", "Planner Engine" ou qualquer outro Engine nomeado. Ele conhece apenas **Module** — uma abstração genérica que um Engine, um Plugin, um Service ou um Package implementam da mesma forma. Se um dia o Kernel precisar de um `if (module.kind === 'engine')` para funcionar, isso é sinal de que a abstração vazou e precisa ser corrigida, não um detalhe de implementação aceitável.

## O que pertence ao Kernel

- **Bootstrap da aplicação** — Configuration Provider, Telemetry, contêiner de dependências, resolução da ordem de inicialização dos Modules a partir de `dependsOn` (ver [BOOTSTRAP.md](BOOTSTRAP.md)).
- **Registro e ciclo de vida dos Modules** — o Kernel sabe que Modules existem, os descobre a partir do [System Manifest](SYSTEM_MANIFEST.md) e os inicializa na ordem certa, mas não conhece a lógica de negócio de nenhum.
- **Carregamento do Plugin System** — descobrir e registrar Plugins a partir de seus manifests (ver [PLUGIN_SYSTEM.md](PLUGIN_SYSTEM.md)) como Modules de `kind: "plugin"`; o Kernel carrega, nunca implementa. (Não coberto pela Release 2 — ver [BOOTSTRAP.md § Como Plugins são descobertos](BOOTSTRAP.md#como-plugins-são-descobertos).)
- **Event Bus** — o mecanismo técnico de publicação/assinatura de eventos (ver [EVENT_MODEL.md](EVENT_MODEL.md)); o *conteúdo* de um evento de domínio pertence ao Module que o publica, não ao Kernel.
- **Health-check e status da plataforma** — `/health/live`, `/health/ready`, `/health/startup` (ver [BOOTSTRAP.md § Health](BOOTSTRAP.md#health--compatível-com-kubernetes)), agregando o estado (`ready`/`degraded`) de cada Module.
- **Contexto de execução transversal** — Tenant, Workspace e User atuais, *resolvidos pelo Identity Engine* (Release 3) e apenas *disponibilizados* pelo Kernel a qualquer Module sem cada um precisar resolvê-los de novo. O Kernel não resolve identidade sozinho — a lógica de "quem é este usuário" pertence ao Identity Engine, não ao Kernel.
- **Bootstrap de Telemetry** — inicializar Logs, Metrics, Tracing e Audit (ver [TELEMETRY.md](TELEMETRY.md)); o *conteúdo* logado é de cada Module.
- **Self-Describing Components** — agregar o descriptor que cada Module expõe sobre si mesmo (ver [SYSTEM_MANIFEST.md](SYSTEM_MANIFEST.md)); o Kernel coleta e disponibiliza esses descriptors, nunca decide o que um Module deveria descrever.

## O que nunca pertence ao Kernel

- Qualquer regra de negócio de domínio — isso é sempre de um Module específico.
- Interpretação de linguagem natural — Intent Engine.
- Resolução de identidade/Tenant/Workspace/permissão — Identity Engine, não o Kernel.
- Decisão de plano de execução — Planner Engine.
- Implementação concreta de qualquer Plugin — o Kernel só conhece o manifest, nunca o código do Plugin.
- Persistência de Mission, Knowledge, Memory — cada uma pertence ao Module dono daquele domínio.
- Apresentação/UI — isso é de `apps/*`.
- Decisão de negócio de qualquer tipo (precificação, aprovação de proposta, escopo de contrato) — o Kernel não tem opinião de negócio, só orquestra quem tem.
- **Conhecimento nomeado de qualquer Engine específico** — reforçando a regra do topo deste documento.

## Teste prático

Uma peça de lógica pertence ao Kernel se, e somente se, remover essa peça quebra **todos** os Modules igualmente (ex: sem bootstrap, nenhum Module inicializa) — e se o Kernel consegue fazer seu trabalho sem nunca perguntar "que tipo de Module é este". Se remover a peça só afeta um Module específico, ou se a lógica precisa saber o `kind` do Module para decidir o que fazer, ela pertence a esse Module — mesmo que pareça "genérica" o suficiente para viver no núcleo.

## Onde vive

Implementação em [packages/kernel](packages/kernel/), a partir da Release 2 — SIGMA Bootstrap do [ROADMAP.md](ROADMAP.md), pré-requisito de toda Release seguinte. A Release 2 é o primeiro incremento do escopo completo descrito aqui — não a totalidade; ver [BOOTSTRAP.md](BOOTSTRAP.md) para o que ela entrega de fato, e [ADR-0038](docs/adr/0038-sigma-bootstrap-nao-kernel-completo.md)/[ADR-0040](docs/adr/0040-bootstrap-nao-conhece-engines.md) para o porquê das distinções.
