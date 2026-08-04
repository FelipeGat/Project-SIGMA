# Playbook: Novo Orçamento

## Gatilho

"Sigma, monte uma proposta para o cliente X" — ou uma etapa do Playbook [Novo Cliente](novo-cliente.md) que exige orçamento formal.

## Contexto necessário

- Cliente e escopo solicitado (produtos/serviços, quantidade, particularidades do pedido).
- Política de precificação vigente (`knowledge/comercial/precificacao.md`).
- Casos similares anteriores, quando existirem (Memory).

## Fases esperadas

1. Reunir escopo e validar contra o catálogo (`knowledge/produtos/`) e itens comerciais reais no Gestor.Alfa.
2. Estruturar a proposta — itens desmembrados (materiais em Produtos, etapas em Serviços, seguindo a convenção já em uso no Gestor.Alfa), aplicando a política de precificação.
3. Gerar peça visual da proposta, quando aplicável (Agent de Design).
4. Vincular a um `pre_cliente_id`/lead no Funil de Vendas, se ainda não vinculado.
5. Submeter para aprovação humana antes do envio ao cliente.

## Agentes e Skills tipicamente envolvidos

- ChatGPT (Estratégia) — estruturação e precificação.
- Gemini (Design) — peça visual, quando aplicável.
- `GestorSkill` (criação do orçamento, vínculo ao funil).

## Pontos de decisão humana

Toda proposta é revisada por um humano antes de ser enviada ao cliente — este Playbook nunca termina em envio automático.

## Critérios de sucesso

Orçamento criado no Gestor.Alfa, corretamente desmembrado e vinculado a um lead no Funil, dentro da política de precificação vigente.

## Conhecimento relacionado

[knowledge/comercial/](../knowledge/comercial/), [knowledge/produtos/](../knowledge/produtos/), [skills/gestor.md](../skills/gestor.md).
