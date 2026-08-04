# ADR-0080: Knowledge da Release 4 é índice estruturado simples — busca semântica fica para a Release 16

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Dois documentos descrevem "Knowledge" com escopos parecidos, mas não idênticos: [MEMORY_ARCHITECTURE.md](../../MEMORY_ARCHITECTURE.md)/[DOMAIN.md](../../DOMAIN.md) atribuem "Knowledge" à Release 4 (Memory Engine), alimentado por `/knowledge`; [ROADMAP.md](../../ROADMAP.md) descreve a Release 16 — Knowledge Engine como o "cérebro documental" do SIGMA, indexando um conjunto bem mais amplo (Clientes, Produtos, Playbooks, Empresa, Projetos, Normas, ADRs, Decisions). Sem uma linha clara, a Release 4 corre o risco de crescer para dentro do escopo da Release 16.

## Decisão

A Release 4 entrega `KnowledgeRecord` como **persistência e consulta estruturada simples** do que já existe em `/knowledge`: indexado por `area` (a taxonomia de pastas já existente), `title`, `sourcePath`, com busca textual direta (`LIKE`/full-text nativo do MariaDB) — sem embeddings, sem ranking semântico, sem nenhuma fonte além de `/knowledge`. A Release 16 — Knowledge Engine matura isso para busca semântica de verdade, sobre uma base de fontes muito mais ampla (ADRs, Decision Logs, Playbooks, etc.), quando chegar sua vez.

## Consequências

- A Release 4 fica com um escopo de Knowledge pequeno e alcançável — indexar arquivos Markdown já existentes, nada além disso.
- Nenhum investimento em infraestrutura de busca semântica (embeddings, vetores) acontece prematuramente, antes de haver conteúdo real suficiente em `/knowledge` para justificá-lo — hoje todas as sete pastas têm só `README.md` de escopo, nenhum conteúdo real ainda ([memory/NEXT.md](../../memory/NEXT.md) do repositório já registra isso como pendência não iniciada).
- Quando `/knowledge` tiver conteúdo real e a Release 16 chegar, a evolução é aditiva sobre o que a Release 4 já persistiu — não uma reescrita.
