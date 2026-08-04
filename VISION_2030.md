# Visão 2030 — de software a plataforma

[VISION.md](VISION.md) descreve por que o SIGMA existe hoje. Este documento descreve para onde ele precisa estar em condição de crescer — não um plano de execução (isso é o [ROADMAP.md](ROADMAP.md)), mas o horizonte que impede decisões de curto prazo de fechar portas que vão ser necessárias mais adiante.

## De software a plataforma

Em 2026, o SIGMA é um sistema: um backend, um frontend, um conjunto fechado de Skills e quatro Agentes de IA. Até 2030, a ambição é que o SIGMA seja uma **plataforma**: um Kernel estável sobre o qual novos Engines, Skills, Agentes e até interfaces (CLI, SDK) podem ser adicionados sem tocar no núcleo — inclusive por outras equipes ou empresas do Grupo Soluções.

Isso muda o que "terminar uma funcionalidade" significa. Não é só ela funcionar — é ela ser extensível por alguém que não é quem a construiu.

## Do monorepo à constelação de repositórios

O nome do repositório (`project-sigma`) foi escolhido deliberadamente amplo, e não `sigma-io`, porque a forma final não é um único artefato. À medida que cada Engine amadurece e ganha ciclo de release próprio, é esperado que o monorepo atual (ver [ADR-0002](docs/adr/0002-estrutura-de-monorepo.md)) se decomponha em repositórios especializados:

- `sigma-core` — Kernel e Engines centrais
- `sigma-api` — camada de API pública
- `sigma-web` / `sigma-mobile` — interfaces
- `sigma-cli` — operação via linha de comando (hoje inexistente, previsto)
- `sigma-sdk` — para sistemas externos (incluindo os do próprio ecossistema Alfa) integrarem com o SIGMA sem depender de detalhes internos
- `sigma-skills` — catálogo de Skills, possivelmente com contribuição de terceiros
- `sigma-agents` — definições de Agentes, especialidades e prompts operacionais

Essa decomposição não é uma decisão da Fase Foundation — é o motivo pelo qual a Fase Foundation insiste tanto em desacoplamento (ver [MANIFESTO.md](MANIFESTO.md)) desde o primeiro módulo. Um monólito bem desacoplado internamente se decompõe em repositórios sem reescrita; um monólito acoplado, não.

## De 4 Agentes a um ecossistema de especialistas

Claude (Engenharia), ChatGPT (Estratégia), Gemini (Design) e Manus (Documentação) são o conjunto inicial. A arquitetura em três camadas (IA / Agente / Skill — [ADR-0004](docs/adr/0004-tres-camadas-ia-agente-skill.md)) existe para que adicionar o quinto, o décimo, ou trocar qualquer um dos quatro atuais, seja configuração, não reengenharia.

## De orquestrador de 5 sistemas a orquestrador de dezenas

O SIGMA nasce orquestrando o ecossistema Alfa conhecido hoje: Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam. A visão de 2030 é que qualquer sistema novo do grupo (incluindo sistemas de terceiros, via `sigma-sdk`) se integre ao SIGMA como Skill, sem que o núcleo precise saber, a priori, que aquele sistema existe.

## De ferramenta interna a diferencial de produto

Hoje o SIGMA é infraestrutura interna da Alfa (ver [PRODUCT.md](PRODUCT.md)). A visão de longo prazo é que a capacidade de orquestração do SIGMA se torne parte do valor entregue junto com os demais produtos do Grupo Soluções — não necessariamente vendida isoladamente, mas como o motivo pelo qual operar com a Alfa é estruturalmente mais eficiente do que operar com um concorrente que não tem essa camada.

## O que não muda

Os princípios do [MANIFESTO.md](MANIFESTO.md) — SIGMA nunca substitui pessoas, nunca pertence a uma IA, prioriza desacoplamento acima de conveniência — não são metas de 2030. São a razão pela qual chegar a 2030 sem precisar reescrever tudo é possível.
