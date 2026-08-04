# ADR-0002: Backend, frontend web e mobile vivem no mesmo repositório (monorepo)

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O SIGMA terá um backend (Laravel), um frontend web (React PWA) e um app mobile (React Native/Expo), todos consumindo a mesma API e o mesmo Design System. Era preciso decidir entre um repositório por aplicação (polyrepo) ou um único repositório (monorepo).

## Decisão

Monorepo único (`project-sigma`, renomeado de `Sigma-IO` — ver [memory/DECISIONS.md](../../memory/DECISIONS.md)), com `backend/`, `frontend-web/` e `frontend-mobile/` na raiz, mais `docs/` compartilhada. O Design System é desenvolvido uma vez e consumido pelas duas frentes de frontend.

## Consequências

- Mudanças que atravessam backend e frontend (ex: novo campo na API de Missão + tela que o exibe) cabem num único PR revisável em conjunto.
- Convenções, ADRs e documentação de domínio ficam num único lugar, sem duplicação entre repositórios.
- Exige disciplina de fronteira interna: `frontend-web` e `frontend-mobile` não devem importar código um do outro além do Design System compartilhado; nenhum deles acessa o banco do `backend` diretamente, apenas via API REST/WebSocket.
- CI/CD precisa ser sensível a path (só builda/testa o que mudou) a partir do momento em que houver pipeline — não é uma preocupação da Fase Foundation.
