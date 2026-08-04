# Próximos passos

## Imediato — aguardando aprovação

1. Revisão da Sprint 0.1 pelo responsável do projeto.
2. Se aprovado: primeiro `git push` do repositório ao GitHub (`FelipeGat/Sigma-IO`, a renomear para `project-sigma` — ver decisão em [DECISIONS.md](DECISIONS.md)).

## Depois do push — Épico de implementação

O primeiro épico de código, conforme [ROADMAP.md](../ROADMAP.md) reestruturado por camadas, é o **Kernel**: bootstrap da plataforma, configuração, contexto de execução, health-check — a fundação sobre a qual todo Engine seguinte roda. Nenhum código será escrito antes de esse épico ser apresentado no formato Objetivo/Escopo/Arquitetura/Dependências/Riscos/Entrega/Testes/Critérios de Aceite e aprovado explicitamente (ver [ADR-0010](../docs/adr/0010-processo-por-epicos-com-aprovacao.md)).

## Não bloqueado, mas não iniciado

Popular `knowledge/` com conteúdo real (hoje só há README de escopo por pasta) pode acontecer em paralelo ao desenvolvimento de código, por quem detém o conhecimento de negócio — não depende de nenhum épico técnico.
