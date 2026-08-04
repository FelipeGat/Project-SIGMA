# ADR-0046: Self-Describing Components

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Documentação de arquitetura (diagramas, listas de dependência) tende a ficar desatualizada em relação ao código assim que o sistema cresce — porque é mantida separadamente, por decisão manual, em vez de derivada do que de fato está em execução. Sem um mecanismo de introspecção, perguntas como "o que depende do event-bus?" ou "quais Plugins oferecem `CreateBudget`?" só têm resposta via grep no código-fonte.

## Decisão

Todo Module — Engine, Plugin, Service ou Agent — implementa `describe()` (ver contrato `Module` em [BOOTSTRAP.md](../../BOOTSTRAP.md)), retornando um descriptor: `id`, `type`, `version`, `status`, `capabilities`, `dependencies`, `health`. O Kernel agrega esses descriptors de todos os Modules carregados. Especificação completa em [SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md).

## Consequências

- Diagrama de arquitetura, mapa de dependências e busca por Capability podem ser gerados a partir do sistema em execução, não mantidos manualmente — nunca ficam desatualizados em relação ao código, porque *são* o código.
- O Bootstrap pode validar dependências declaradas contra Modules de fato presentes **antes** de completar o boot — uma dependência ausente é um erro de subida explícito, não uma falha em runtime na primeira chamada.
- Abre caminho, no horizonte de [VISION_2030.md](../../VISION_2030.md), para uma interface de administração que não precisa de nenhuma tela escrita à mão para listar o que está rodando.
- Exige que todo Module, desde sua primeira implementação, mantenha seu descriptor honesto — um Module que declara uma Capability que não implementa de fato, ou omite uma dependência real, quebra a confiabilidade de todo o mecanismo. Isso é tratado como defeito, não como detalhe menor.
- Implementação (contrato `describe()`, agregação pelo Kernel) nasce com a Release 2 — SIGMA Bootstrap.
