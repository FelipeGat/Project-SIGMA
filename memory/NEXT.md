# Próximos passos

## Imediato

1. Apresentar a proposta revisada (revisão 2) da Release 2 — SIGMA Bootstrap ao Product Owner, com todas as alterações obrigatórias incorporadas.
2. Aguardar confirmação final antes de escrever a primeira linha de código.
3. Se confirmado: commit local já pronto — só falta push, autorizado no mesmo momento em que a confirmação final chegar (não assumido antecipadamente).

## Resolvido nesta revisão (não pendente mais)

- ~~Onde vive o schema de multiempresa~~ — resolvido: Identity Engine, Release 3, própria (não Memory Engine). Ver [ADR-0039](../docs/adr/0039-identity-engine.md).

## Ainda aguardando confirmação do Product Owner

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova acima dela. Não bloqueia a Release 2; relevante antes da Release 6 (Planner) ou 7 (Intent).

## Depois da confirmação — início do código

Primeira linha de código do Project SIGMA: Release 2 — SIGMA Bootstrap, exatamente no escopo de [docs/releases/0002-sigma-bootstrap.md](../docs/releases/0002-sigma-bootstrap.md) — Configuration Provider, Telemetry, DI Container, Module genérico (nunca Engine), System Manifest, Self-Describing Components, Lifecycle estendido, Health estilo Kubernetes. Nada de Mission, Identity, IA, ou Plugins. Ao final, publicar o Decision Log correspondente ([ADR-0047](../docs/adr/0047-decision-log-por-release.md)).

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
