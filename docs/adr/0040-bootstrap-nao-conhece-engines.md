# ADR-0040: Bootstrap nunca conhece Engines — apenas Modules

- **Status**: Aceito — refina [ADR-0038](0038-sigma-bootstrap-nao-kernel-completo.md)
- **Data**: 2026-08-04

## Contexto

A documentação até aqui falava em "Kernel inicializa Engines" — o que implicitamente dá ao Kernel conhecimento nomeado sobre o que um Engine é. Isso é um vazamento de abstração: o dia em que o SIGMA precisar carregar algo que não é um Engine nem um Plugin (um Service, um novo tipo de componente ainda não imaginado), o Kernel precisaria de código novo só para reconhecer esse novo tipo — quando na verdade seu trabalho (resolver ordem de boot, injetar dependências, expor health) é idêntico para qualquer um deles.

## Decisão

O Bootstrap/Kernel conhece apenas **Module** — uma abstração única com `name`, `kind`, `dependsOn`, `config()`, `register()`, `boot()`, `describe()` (ver [BOOTSTRAP.md](../../BOOTSTRAP.md)). Engine, Plugin, Service e Package são valores do campo `kind`, metadado sem efeito no comportamento do Kernel. Nenhuma lógica do Kernel ramifica com base em `kind`.

## Consequências

- Adicionar um novo tipo de componente ao SIGMA no futuro (algo além de Engine/Plugin/Service/Package) não exige nenhuma mudança no Kernel — só um novo Module com esse `kind`.
- O teste prático de [KERNEL.md](../../KERNEL.md) ganha um critério objetivo a mais: se uma peça de lógica do Kernel precisa perguntar "que tipo de Module é este" para decidir o que fazer, ela não pertence ao Kernel.
- Exige disciplina de revisão: é tentador, sob pressão, adicionar um `if (kind === 'plugin')` "só para esse caso especial" — isso é tratado como defeito de arquitetura a partir desta ADR, não uma exceção aceitável.
- A implementação de referência do contrato `Module` nasce na Release 2 — SIGMA Bootstrap; nenhum código ainda existe.
