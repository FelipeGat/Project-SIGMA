# ADR-0032: SIGMA Language (SGL) como camada intermediária de representação de Intent

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O Envelope (JSON) é o formato de transporte entre Engines, mas JSON aninhado é um alvo frágil para um modelo de linguagem gerar diretamente a partir de uma frase em português — mais fácil de produzir estrutura malformada do que um formato mais raso, próximo de YAML. Faltava uma representação pensada especificamente para a etapa em que uma Intent ainda está sendo formada, antes de virar o Envelope definitivo.

## Decisão

Cria-se a **SGL — SIGMA Language**: uma gramática rasa de blocos (`INTENT`, `MISSION`, `SUBTASK`) com campos `chave: valor` e listas indentadas, com mapeamento determinístico e sem perda para o Envelope. Especificação completa em [SGL.md](../../SGL.md). O Intent Engine (Release 7) produz SGL a partir de linguagem natural; o Planner Engine (Release 6) consome SGL (ou o JSON equivalente).

## Consequências

- Reduz a taxa de erro estrutural na interpretação de linguagem natural por IA — um modelo tem mais facilidade em produzir um formato raso e repetitivo do que JSON aninhado correto na primeira tentativa.
- Cria um novo artefato a ser versionado e testado (o mapeamento SGL↔JSON) — trabalho adicional na Release 6 ou 6, quem for construída primeiro segundo a Ordem de Desenvolvimento ([ADR-0031](0031-ordem-runtime-vs-desenvolvimento.md)).
- SGL é deliberadamente raso (sem aninhamento além de bloco → campo → lista) — qualquer estrutura mais complexa pertence ao JSON do Envelope, não a SGL. Isso evita que SGL vire, com o tempo, uma segunda linguagem de propósito geral concorrendo com o próprio Envelope.
- Nenhum parser/serializer é implementado nesta Release — apenas a gramática e o mapeamento estão especificados.
