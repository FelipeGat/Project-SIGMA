# ADR-0023: Governança do projeto formalizada em /council

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Até aqui, todos os papéis envolvidos na construção do SIGMA (definição de arquitetura, revisão crítica, implementação, decisão de negócio) aconteciam de forma implícita em conversa, sem responsabilidades e limites documentados por papel. Isso funciona numa equipe pequena, mas não escala — quando o SIGMA tiver dezenas de Agents, Plugins e colaboradores, a ausência de papéis explícitos tende a gerar decisões inconsistentes, tomadas por quem estiver disponível no momento, não por quem tem a responsabilidade certa.

## Decisão

Governança do projeto formalizada em [/council](../../council/): Product Owner (Felipe), CTO (ChatGPT), Lead Engineer (Claude), Creative (Gemini), Documentation (Manus) — cada um com Missão, Responsabilidades, Limites e Forma de Decisão documentados. Distinto de [/agents](../../agents/), que documenta personas de execução runtime dentro de uma Mission, não governança do projeto.

## Consequências

- Autoridade de decisão fica explícita: o Product Owner tem aprovação final; os demais papéis recomendam e revisam, cada um em sua área.
- Reduz ambiguidade sobre quem deveria ter sinalizado o quê quando algo dá errado — o papel de CTO existe justamente para revisão crítica de arquitetura antes da aprovação, por exemplo.
- O paralelo entre Council e Agents (mesmas quatro IAs, papéis diferentes) é deliberado — reforça que a mesma especialidade (Engenharia, Estratégia, Design, Documentação) se aplica tanto à governança do projeto quanto à execução de Missions, sem confundir os dois níveis.
- É simbólico enquanto a equipe for pequena — o valor real desta ADR se manifesta quando o número de colaboradores e decisões crescer; documentar cedo é mais barato do que formalizar depois que a ambiguidade já causou uma decisão inconsistente.
