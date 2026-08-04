# Próximos passos

## Imediato — aguardando aprovação

1. Revisão da Sprint 0.2 pelo Product Owner.
2. Se aprovado: primeiro `git push` do repositório ao GitHub (`FelipeGat/Project-SIGMA`, já renomeado e com `origin` local atualizado).

## Depois do push — Épico de implementação

O primeiro épico de código, conforme [ROADMAP.md](../ROADMAP.md), é a camada **L1 — Kernel**: bootstrap da plataforma, configuração, contexto de execução, health-check, schema fundacional de multiempresa (Tenant/Company/Workspace/User/Role — ver [MULTITENANCY.md](../MULTITENANCY.md)), bootstrap de Telemetry e do `services/event-bus`. Nenhum código será escrito antes de esse épico ser apresentado no formato Objetivo/Escopo/Arquitetura/Dependências/Riscos/Entrega/Testes/Critérios de Aceite e aprovado explicitamente (ver [ADR-0010](../docs/adr/0010-processo-por-epicos-com-aprovacao.md)).

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real (hoje só há README de escopo por pasta) pode acontecer em paralelo ao desenvolvimento de código, por quem detém o conhecimento de negócio — não depende de nenhum épico técnico.
- Criar a Organização no GitHub (`Alfa-Solucoes` ou `Grupo-Solucoes`) sugerida pelo Product Owner e mover `Project-SIGMA` para dentro dela — ação de conta do GitHub, fora do escopo do que posso executar diretamente; decisão e execução ficam com o Product Owner.
