# Council

Governança do Project SIGMA — quem decide sobre o *projeto em si*, não sobre a execução de uma Mission em runtime. Não confundir com [/agents](../agents/): agents/ documenta as personas que o **Agent Engine** delega para executar Subtasks dentro de uma Mission; council/ documenta os papéis (humanos e de IA) que decidem sobre a arquitetura, o produto e a direção do SIGMA enquanto ele é construído.

É simbólico hoje, com uma equipe pequena. A razão para existir desde a Fase Foundation: quando o SIGMA tiver dezenas de Agents, Plugins e colaboradores, a linha entre "quem decide o rumo" e "quem executa uma tarefa" precisa já estar clara — não é algo para desenhar depois que a ambiguidade já causou dano.

| Papel | Ocupado por | Responsabilidade central |
|---|---|---|
| [ProductOwner.md](ProductOwner.md) | Felipe | Prioridade final, aprovação de épicos, dono do produto e das decisões de negócio |
| [CTO.md](CTO.md) | ChatGPT | Estratégia técnica de alto nível, revisão crítica de arquitetura |
| [LeadEngineer.md](LeadEngineer.md) | Claude | Arquitetura técnica, implementação, qualidade de código |
| [Creative.md](Creative.md) | Gemini | Identidade visual e direção de design do próprio SIGMA |
| [Documentation.md](Documentation.md) | Manus | Consistência documental, manutenção de glossário e ADRs ao longo do tempo |

Cada papel tem responsabilidades, limites e forma de tomar decisão documentados no arquivo correspondente. Nenhum papel do Council substitui o processo de aprovação por épico definido em [ADR-0010](../docs/adr/0010-processo-por-epicos-com-aprovacao.md) — o Council informa a decisão; o Product Owner aprova.
