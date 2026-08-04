# Próximos passos

## Imediato — aguardando aprovação

1. Revisão da Release 1 — SIGMA Protocol pelo Product Owner (documento [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md), mais as ADRs 0024–0029 e a atualização de `DOMAIN.md`/`ARCHITECTURE.md`/`PLUGIN_SYSTEM.md`/manifests de Plugin que dele decorrem).
2. Confirmar especificamente a ordem **Planner Engine (Release 5) antes do Intent Engine (Release 6)** — sinalizada como tensão em [ADR-0025](../docs/adr/0025-protocol-antecede-kernel.md), não assumida silenciosamente.
3. Se aprovado: `git push` da Release 1 ao GitHub.

## Depois da aprovação — Release 2

A primeira Release de código é o **Kernel** (bootstrap, contexto de execução, schema fundacional de multiempresa, Telemetry, event-bus), agora implementando o Envelope de resposta desde o primeiro endpoint. Nenhum código será escrito antes de essa Release ser apresentada no formato Objetivo/Escopo/Arquitetura/Dependências/Riscos/Entrega/Testes/Critérios de Aceite e aprovada explicitamente (ver [ADR-0010](../docs/adr/0010-processo-por-epicos-com-aprovacao.md)).

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real pode acontecer em paralelo ao desenvolvimento de código.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
- Definir quem configura o nível de Autonomia Progressiva por User/Role na prática (ver [ADR-0029](../docs/adr/0029-autonomia-progressiva.md)) — adiado para a Release 7/8, quando Skill Engine e Agent Engine de fato aplicam esse gate.
