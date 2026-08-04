# ADR-0010: Desenvolvimento avança por épicos únicos, com aprovação obrigatória antes de implementar

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Dado o tamanho da visão do SIGMA (16 domínios de gestão, orquestração multi-IA, dezenas de Skills previstas), há risco real de dispersão: começar várias frentes ao mesmo tempo, gerar código parcial em múltiplas direções, e nunca ter um fluxo completo (Missão ponta a ponta) funcionando de verdade.

## Decisão

O SIGMA é desenvolvido um épico por vez. Nenhum épico começa a ser implementado sem antes ser apresentado no formato Objetivo, Escopo, Arquitetura, Dependências, Riscos, Entrega, Testes e Critérios de Aceite, e aprovado explicitamente. Todo código entregue por um épico deve ter, no mínimo: documentação, testes, logs, tratamento de erros, versionamento, nomes padronizados e alta legibilidade — comentários apenas onde o "porquê" não é óbvio pelo código.

## Consequências

- A qualquer momento, existe no máximo um épico "em voo" — reduz trabalho em progresso e força priorização explícita do que vem a seguir (ver [ROADMAP.md](../../ROADMAP.md)).
- Cada épico aprovado produz um incremento verificável e coerente do sistema, nunca um esqueleto que só funciona quando outro épico não iniciado é somado a ele.
- Exige disciplina de quem aprova: revisar a proposta do épico (arquitetura, riscos, critérios de aceite) antes de autorizar código — pular essa etapa anula o benefício do processo.
- Pode parecer mais lento no curto prazo do que "abrir várias frentes" — aceito conscientemente como o custo de evitar retrabalho em um domínio ainda em maturação.
