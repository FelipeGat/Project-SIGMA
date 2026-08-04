# Knowledge

Esta pasta não é documentação do SIGMA — é o **conhecimento de negócio da Alfa** que alimenta o Memory Engine ([DOMAIN.md](../DOMAIN.md)). É o material bruto a partir do qual, quando o Épico E5 — Knowledge & Memory for implementado, o SIGMA passa a consultar contexto estruturado em vez de depender de alguém lembrar e explicar de novo a cada Mission.

Diferença em relação a `/docs`: `docs/` explica **como o SIGMA é construído**. `knowledge/` registra **o que a Alfa sabe sobre seu próprio negócio** — independente do SIGMA existir ou não.

## Estrutura

| Pasta | Conteúdo |
|---|---|
| [clientes/](clientes/) | Perfil, histórico e particularidades de clientes relevantes — não duplica o cadastro do Gestor.Alfa, complementa com contexto qualitativo que um sistema de CRUD não guarda |
| [produtos/](produtos/) | Catálogo de produtos/serviços da Alfa e das empresas do Grupo Soluções, em linguagem de negócio |
| [empresa/](empresa/) | Estrutura organizacional, empresas do grupo, papéis e responsabilidades |
| [processos/](processos/) | Como a Alfa faz o que já sabe fazer bem — a versão descritiva; a versão executável equivalente vira [Playbook](../playbooks/) |
| [comercial/](comercial/) | Política de precificação, argumentação de venda, objeções recorrentes e como respondê-las |
| [marketing/](marketing/) | Posicionamento, identidade visual, público-alvo por produto/empresa |
| [engenharia/](engenharia/) | Padrões técnicos e decisões de arquitetura dos sistemas do ecossistema Alfa que o SIGMA precisa conhecer para orquestrá-los corretamente |

## Como popular

Cada subpasta começa com um `README.md` descrevendo seu escopo e o formato esperado de conteúdo — não com dados fictícios. O conteúdo real é adicionado por quem detém o conhecimento (comercial, técnico, diretoria), incrementalmente, e passa a alimentar o Memory Engine a partir do Épico E5.
