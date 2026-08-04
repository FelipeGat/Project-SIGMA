# Próximos passos

## Imediato

1. Aguardar confirmação do Product Owner para dar push do código da Release 2.
2. Validar `docker-compose up --build` num ambiente com Docker Desktop ativo (não verificado nesta sessão — ver Decision Log).
3. Confirmar se o ambiente de CI/produção já roda PHP 8.4 real (ADR-0009) antes de qualquer deploy — local ficou em 8.2.

## Aguardando confirmação do Product Owner (não bloqueia a Release 2)

1. **[ADR-0036](../docs/adr/0036-objetivo-e-campo-da-intent.md)** — "Objetivo" como campo de Intent vs. camada nova. Relevante antes da Release 6/7.

## Depois da Release 2

Release 3 — Identity Engine, mesmo processo de quatro fases: Proposal → Architecture Review → Implementation → Validation. Primeira Release a introduzir banco de dados (MariaDB) e o schema de multiempresa (Tenant/Company/Workspace/User/Role). Primeiro Module de domínio real a implementar `IModule` — vai testar se o contrato genérico se sustenta fora de infraestrutura pura (risco já sinalizado na proposta da Release 2).

## Não bloqueado, mas não iniciado

- Popular `knowledge/` com conteúdo real.
- Criar a Organização no GitHub (`Alfa-Solucoes`/`Grupo-Solucoes`) sugerida pelo Product Owner — ação de conta fora do escopo do que posso executar diretamente.
