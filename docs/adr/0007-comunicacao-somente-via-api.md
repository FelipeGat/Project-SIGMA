# ADR-0007: Comunicação exclusivamente via API — proibido acesso direto a banco de outros sistemas

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Seria tecnicamente mais rápido, em alguns casos, o SIGMA ler diretamente o banco de dados do Gestor.Alfa, do AlfaControl ou de outro sistema do ecossistema Alfa, em vez de esperar uma API ser exposta. Esse atalho é comum em integrações internas de uma mesma empresa e é também a forma mais eficiente de acoplar dois sistemas de forma silenciosa e irreversível.

## Decisão

O SIGMA nunca acessa diretamente o banco de dados de outro sistema. Toda comunicação — leitura ou escrita — acontece através da API pública do sistema de origem, encapsulada numa Skill. Se a API necessária não existe, ela é um pré-requisito a ser criado no sistema de origem, não um motivo para abrir exceção.

## Consequências

- Cada sistema integrado mantém controle total sobre seu próprio schema de banco e pode evoluí-lo sem quebrar o SIGMA.
- Toda leitura/escrita do SIGMA em um sistema externo respeita as regras de negócio e validações que já existem na API desse sistema — não há risco de o SIGMA gravar um estado inválido direto no banco.
- Sistemas do ecossistema Alfa que hoje não expõem API suficiente para um caso de uso do SIGMA precisam ganhar esse endpoint antes que a Skill correspondente possa ser construída — isso é trabalho visível e priorizável, não um custo escondido.
