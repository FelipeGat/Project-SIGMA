# Produto — SIGMA

Este documento descreve o SIGMA como produto: para quem existe, que problema resolve e como se organiza — não como roadmap nem como arquitetura técnica (ver [ROADMAP.md](ROADMAP.md) e [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md)).

## Qual problema resolve

Hoje, coordenar trabalho entre os sistemas da Alfa (Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam) e entre pessoas (comercial, técnico, administrativo) depende de alguém carregar o contexto na cabeça: em qual sistema lançar o quê, quem avisar, o que já foi feito antes num caso parecido. Esse conhecimento operacional vive disperso — em conversa de WhatsApp, na memória de quem já fez aquilo antes, em planilhas paralelas. SIGMA existe para capturar essa coordenação como estrutura: uma solicitação em linguagem natural vira uma Missão rastreável, que usa o conhecimento já acumulado (Knowledge/Memory) e os sistemas certos (Skills) para se cumprir.

## Quem usa

- **Quem opera a empresa** — diretoria, comercial, técnico/coordenação de obras e atendimentos, administrativo/financeiro. Dispara Missões em linguagem natural e acompanha seu progresso.
- **Os próprios sistemas do ecossistema Alfa** — Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam podem, no futuro, disparar Missões via API (ex: um orçamento fechado no Gestor.Alfa abre uma Missão de implantação no SIGMA), sem intervenção humana.

## Quem paga

Inicialmente, o custo do SIGMA é interno — é infraestrutura operacional da Alfa Soluções Tecnológicas, não um produto vendido a terceiros. O retorno é produtividade (menos tempo humano coordenando, mais Missões concluídas por unidade de tempo) e conhecimento acumulado (Knowledge/Memory que não se perde quando uma pessoa muda de função).

À medida que o SIGMA amadurece, ele pode se tornar um diferencial competitivo oferecido junto com os demais produtos do Grupo Soluções (ex: uma empresa do grupo que já usa Gestor.Alfa/AlfaControl ganhando orquestração via SIGMA) — mas isso é uma direção possível, não um requisito da Fase Foundation. Ver [VISION_2030.md](VISION_2030.md).

## Personas

| Persona | O que faz no SIGMA |
|---|---|
| **Diretoria** | Dispara Missões de alto nível ("participe da reunião do cliente X"), acompanha visão consolidada do que está em execução em toda a empresa |
| **Comercial** | Missões relacionadas a proposta, funil, cliente — SIGMA orquestra Gestor.Alfa/Funil e registra contexto de cada negociação |
| **Técnico / Coordenação** | Missões relacionadas a obra, atendimento, implantação — SIGMA orquestra Central Operacional, agenda, checklist |
| **Administrativo / Financeiro** | Missões relacionadas a cobrança, contrato, follow-up financeiro |
| **Sistema externo** (Gestor.Alfa, AlfaControl...) | Dispara e recebe Missões via API, sem interface humana |

Personas detalhadas por módulo (com jornada e critérios de sucesso específicos) são escritas quando o épico correspondente é proposto — não antecipadas aqui para não engessar decisões que ainda não têm dado real por trás.

## Módulos (visão de produto, não de código)

Correspondem aos contextos descritos em [docs/architecture/ARCHITECTURE.md §4](docs/architecture/ARCHITECTURE.md):

- **Missões** — o que está sendo pedido e executado agora.
- **Conhecimento & Memória** — o que a empresa e o SIGMA já sabem.
- **Agentes & Skills** — quem executa e com quais integrações.
- **Clientes, Empresas, Projetos** — o que está sendo orquestrado, para quem.
- **Times & Usuários** — quem opera o sistema.
- **Processos & Playbooks** — como a empresa faz as coisas que já sabe fazer bem, capturado para o SIGMA repetir e melhorar.
- **Eventos, Logs & Automações** — rastreabilidade e reação automática.

## Visão de longo prazo

Ver [VISION_2030.md](VISION_2030.md) — este documento descreve o produto como ele precisa ser desde já para sustentar aquela visão, não a visão em si.
