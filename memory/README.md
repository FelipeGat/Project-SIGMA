# Memory (do projeto)

Não confundir com o `Memory Engine` do domínio SIGMA ([DOMAIN.md](../DOMAIN.md)) — aquele é o que o *sistema* aprende sobre as Missions que executa. Esta pasta é a memória operacional do *projeto* SIGMA em si: onde estamos, o que vem a seguir, e o que já foi decidido — para que qualquer pessoa (ou qualquer sessão de trabalho com um Agent) retome o contexto sem depender de alguém reexplicar do zero.

| Arquivo | Conteúdo |
|---|---|
| [STATE.md](STATE.md) | Onde o projeto está agora |
| [NEXT.md](NEXT.md) | O que vem a seguir, e o que está bloqueado aguardando o quê |
| [DECISIONS.md](DECISIONS.md) | Log cronológico de decisões — inclusive as menores, que não justificam uma ADR própria |

Decisões arquiteturais formais (com contexto/consequências detalhados) ficam em [docs/adr/](../docs/adr/), não aqui. `DECISIONS.md` é o registro leve e cronológico; uma ADR é referenciada a partir dele quando existir.
