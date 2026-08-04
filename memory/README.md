# Memory (do projeto)

Não confundir com o `Memory Engine` do domínio SIGMA ([DOMAIN.md](../DOMAIN.md)) — aquele é o que o *sistema* aprende sobre as Missions que executa. Esta pasta é a memória operacional do *projeto* SIGMA em si: onde estamos, o que vem a seguir, e o que já foi decidido — para que qualquer pessoa (ou qualquer sessão de trabalho com um Agent) retome o contexto sem depender de alguém reexplicar do zero.

| Arquivo | Conteúdo |
|---|---|
| [STATE.md](STATE.md) | Onde o projeto está agora |
| [NEXT.md](NEXT.md) | O que vem a seguir, e o que está bloqueado aguardando o quê |
| [DECISIONS.md](DECISIONS.md) | Log cronológico de decisões — inclusive as menores, que não justificam uma ADR própria |

Decisões arquiteturais formais (com contexto/consequências detalhados) ficam em [docs/adr/](../docs/adr/), não aqui. `DECISIONS.md` é o registro leve e cronológico; uma ADR é referenciada a partir dele quando existir.

## Esta pasta — não a memória de nenhuma ferramenta de IA — é a fonte da verdade

Ver [ADR-0059](../docs/adr/0059-repositorio-e-fonte-da-verdade.md). Ferramentas de IA usadas na colaboração (Claude ou qualquer outra) podem manter memória própria, externa a este repositório, para acelerar retomar o trabalho entre sessões — isso é um **cache de conveniência**, nunca um registro autoritativo. Nenhuma decisão crítica do SIGMA depende dessa memória externa existir, estar correta, ou sequer estar disponível. O teste é simples: se a IA usada num dia esquecer tudo, ou for substituída por outra completamente diferente, **basta clonar este repositório** — começando por este `memory/`, [docs/adr/](../docs/adr/) e [docs/releases/](../docs/releases/) — para retomar o desenvolvimento exatamente de onde parou, sem precisar de nenhum histórico de conversa anterior.
