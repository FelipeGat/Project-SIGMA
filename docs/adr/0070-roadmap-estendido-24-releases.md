# ADR-0070: Roadmap estendido a 24 Releases — cinco Engines novos, Gateway/API própria, cinco componentes estruturais sinalizados

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O [ROADMAP.md](../../ROADMAP.md) ia até a Release 14 (Analytics), pensado sem uma visão explícita de longo prazo (5-10 anos) sobre o que o SIGMA precisa se tornar depois que Mission/Planner/Intent/Skill/Agent estiverem prontos. O Product Owner, atuando como CTO/arquiteto de plataforma, mapeou o que falta estruturalmente: um "cérebro documental" vivo (hoje só documentação estática em `/knowledge`), uma representação de estado do mundo real além de consulta a banco (Digital Twin como Engine própria, não só um conceito), um registro formal de quem pode executar o quê (evolução do Plugin System), um mecanismo de colaboração multi-IA sem intermediação humana constante (o "Council" já esboçado em `/council`, mas nunca como Engine executável), e execução concorrente de múltiplos Agents.

## Decisão

O Roadmap passa de 14 para **24 Releases**. Inserções em relação à lista anterior:

- **Release 3.5 — Architecture Consolidation** (já em andamento, ver [docs/releases/0003.5-architecture-consolidation.md](../releases/0003.5-architecture-consolidation.md)) — não muda o produto, fortalece a base antes da Memory Engine.
- **Release 12 — Gateway/API** — a superfície pública de API do SIGMA (REST/GraphQL), construída sobre `services/gateway` (infraestrutura desde a Release 2), antes da Release 13 (Interfaces) consumi-la. Empurra a antiga Release 12 (Interfaces) para 13, a antiga 13 (Automation) para 14, e a antiga 14 (Analytics) para 15 — renumeração mecânica, sem mudar a natureza de nenhuma delas (mesmo padrão do [ADR-0039](0039-identity-engine.md)).
- **Release 16 — Knowledge Engine** — o "cérebro documental": Clientes, Produtos, Playbooks, Empresa, Projetos, Normas, ADRs, Decisions, tudo indexado e consultável, não apenas arquivos estáticos em `/knowledge`.
- **Release 17 — Digital Twin** — [DIGITAL_TWIN.md](../../DIGITAL_TWIN.md) já existe como conceito desde a Release 1 e ganha os primeiros Twins reais na Release 4 (Memory Engine); esta Release é a maturação do conceito em Engine própria — um modelo interno do estado do mundo (Cliente/Projetos/Reuniões/Chamados/Financeiro/Orçamentos/Relacionamentos), não apenas consulta a banco sob demanda.
- **Release 18 — Capability Registry** — evolução formal do [Plugin System](../../PLUGIN_SYSTEM.md): `Capability → Plugin → Agent → Permissões → Dependências`, para que o Planner saiba exatamente quem consegue executar cada ação antes de tentar.
- **Release 19 — Council Engine** — o `/council` (hoje um conjunto de documentos de papéis — ProductOwner/CTO/LeadEngineer/Creative/Documentation) vira mecanismo executável de colaboração entre IAs (Claude/ChatGPT/Gemini/Manus) com aprovação automática dentro de limites definidos, reduzindo a intermediação humana constante.
- **Release 20 — Multi-Agent Runtime** — execução concorrente de múltiplos Agents (ex: Marketing, Financeiro, Jurídico, Comercial, Desenvolvimento simultaneamente), não mais um Agent de cada vez.
- **Release 21 — Marketplace**, **Release 22 — Cloud Sync**, **Release 23 — Production Hardening** — nomeadas, escopo ainda não detalhado (a detalhar quando a Release anterior estiver perto de concluir, mesmo princípio de "o roadmap distante não é desenhado em detalhe hoje" já em vigor desde a criação do ROADMAP.md).
- **Release 24 — SIGMA v1.0** — marco final desta fase do roadmap.

**Cinco componentes estruturais sinalizados, ainda sem Release própria**: Scheduler (execução agendada, ex: "08:00 → Executar Missão → Enviar relatório"), Secrets Manager (credenciais de provedores — OpenAI/Claude/GitHub/Telegram/SMTP/Banco — criptografadas, hoje só Configuration Provider por Module), Cache Layer (Mission/Memory/Planner vão precisar), Observability (Tracing/Metrics/Performance/Latency/Errors — hoje só Logs, ver [TELEMETRY.md](../../TELEMETRY.md)), Policy Engine (unifica "pode? até quanto? precisa aprovação? executa sozinho?" — hoje espalhado entre Autonomia Progressiva e Permission). Nenhum tem Release numerada ainda — ficam registrados aqui e em `memory/NEXT.md` para não se perderem; cada um provavelmente se encaixa dentro de uma Release já listada (Policy Engine em Capability Registry ou Audit; Observability em Audit; Secrets/Cache/Scheduler possivelmente em Production Hardening ou releases próprias a definir).

**Pendência explícita, não resolvida por esta ADR**: o Roadmap recebido do Product Owner lista `6 — Intent Engine, 7 — Planner Engine`, invertendo a ordem já decidida e documentada em [ADR-0031](0031-ordem-runtime-vs-desenvolvimento.md) (`6 — Planner, 7 — Intent`, deliberada: Planner construído primeiro com Intents mockadas, Intent Engine as substitui depois). Esta ADR **mantém a numeração de ADR-0031** (Planner=6, Intent=7) até confirmação explícita do Product Owner sobre se a nova tabela pretendia reabrir essa decisão ou foi uma simplificação da visão de alto nível — sinalizado, não decidido silenciosamente.

## Consequências

- `ROADMAP.md` passa a refletir a visão de 5-10 anos, não só o que está imediatamente à frente — decisões de arquitetura em Releases próximas (ex: Memory Engine, Release 4) já podem ser tomadas sabendo que Knowledge Engine, Digital Twin e Capability Registry virão depois e vão consumir o que a Memory Engine expuser.
- Toda referência cruzada a "Release 12/13/14" em outros documentos (`apps/web/README.md`, `apps/mobile/README.md`, `council/Creative.md`, `EVENT_MODEL.md`, `DOMAIN_EVENTS.md`, `packages/design-system/README.md`, `TELEMETRY.md`, ADR-0019, ADR-0034) precisa de atualização mecânica para os novos números (13/14/15) — feito junto desta ADR, mesmo tratamento dado à renumeração anterior de ADR-0039.
- Release 4 (Memory Engine) é formalmente reconhecida como o segundo marco mais importante do projeto depois da Foundation — toda a cadeia seguinte (Mission/Intent/Planner/Agent/Council) depende da qualidade do que ela expuser. Não muda o escopo já definido para a Release 4, mas eleva o cuidado esperado na sua Proposal/Architecture Review.
