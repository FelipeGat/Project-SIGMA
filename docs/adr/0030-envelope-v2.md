# ADR-0030: Envelope v2 — correlationId, actor, intent, capability, audit

- **Status**: Aceito — revisa [ADR-0026](0026-envelope-de-resposta-padronizado.md)
- **Data**: 2026-08-04

## Contexto

A primeira versão do Envelope (`success`, `data`, `error`, `mission`, `workspace`, `events`, `memory`, `nextActions`, `logs`) cobria o essencial, mas faltavam campos que só ficam dolorosamente óbvios em produção: como correlacionar todas as chamadas de uma mesma Intent ao longo do tempo, quem originou a cadeia, e um jeito de auditar autorização sem inflar cada resposta com uma cópia do log inteiro.

## Decisão

O Envelope ganha: `protocolVersion`, `correlationId`, `requestId`, `timestamp`, `actor`, `intent` (com `objective`), `capability`, `warnings` e `audit` (metadados de autorização). Os campos `mission`/`workspace` são renomeados para `missionId`/`workspaceId`. O campo `logs` é removido — substituído pela combinação `correlationId` + consulta ao Audit Engine. Especificação completa em [SIGMA_PROTOCOL.md §1](../../SIGMA_PROTOCOL.md#1-o-envelope).

## Consequências

- `correlationId` permite reconstruir toda a árvore de uma Intent (todas as Missions, Subtasks e chamadas de Capability que dela resultaram) sem precisar que cada resposta carregue seu próprio histórico — consulta-se o Audit Engine por esse identificador.
- `actor`, `intent` e `capability` tornam cada resposta autoexplicativa: quem pediu, o quê, e através de qual Capability — sem precisar cruzar com outra fonte para entender o contexto de uma resposta isolada.
- Remover `logs` do corpo da resposta reduz o tamanho de cada Envelope e evita duplicar dado que já existe, de forma mais completa, no Audit Engine.
- Todo Plugin, Agent e canal já especificado (Sprint 0.1/0.2) precisa ser revisado para produzir este formato — nenhum manifest de Plugin muda por causa desta ADR (o Envelope é produzido pelo Skill Engine, não pelo Plugin), mas a documentação de cada um foi conferida contra este novo formato.
