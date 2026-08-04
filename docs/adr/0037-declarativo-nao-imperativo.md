# ADR-0037: SIGMA é declarativo, nunca imperativo

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Todo exemplo de Intent usado até aqui na documentação era, na prática, uma lista de instruções em linguagem natural ("participe da reunião, atualize o Gestor, ajuste o orçamento, avise o Victor") — o SIGMA decompõe em Missions, mas o usuário ainda estava, na forma, ditando os passos. Isso deixa aberta a possibilidade de o sistema evoluir para apenas "traduzir" comandos, sem nunca precisar decidir de verdade o que fazer — o oposto do que [ADR-0028](0028-intencao-nao-comando.md) pretende.

## Decisão

Uma Intent declara um **estado desejado**, não uma sequência de passos: não *"atualize o orçamento"*, mas *"o orçamento da Sea Master precisa refletir as decisões tomadas nesta reunião"*. O SIGMA decide o como — qual Capability usar, em que ordem, com qual Agent. Ver [MANIFESTO.md](../../MANIFESTO.md) e [SIGMA_PROTOCOL.md §2](../../SIGMA_PROTOCOL.md#2-intenção-não-comando).

## Consequências

- Uma Intent registrada permanece válida mesmo que a implementação por trás mude inteiramente (nova API do Gestor.Alfa, novo provedor de IA, nova Skill) — o desacoplamento entre intenção e implementação, já um princípio do [MANIFESTO.md](../../MANIFESTO.md), passa a valer também para a forma como uma Intent é expressa, não só para a arquitetura por trás dela.
- Exige que o Planner Engine (Release 6) seja capaz de decidir uma sequência de passos a partir de um estado desejado — mais complexo do que apenas ordenar uma lista já dada pelo usuário. Esse é precisamente o trabalho que justifica a existência do Planner Engine como Engine próprio, não uma função trivial.
- Toda funcionalidade nova é avaliada por este princípio: se ela só funciona quando o usuário especifica o passo a passo, ela está pedindo para ser um script externo ao SIGMA, não uma Mission.
- Os exemplos já publicados na documentação (Sea Master) foram atualizados nesta revisão para refletir a forma declarativa, evitando que a documentação ensine, por exemplo, o padrão errado.
