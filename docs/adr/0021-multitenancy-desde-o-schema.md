# ADR-0021: Multiempresa (multi-tenant) desde o schema, nunca retrofitado

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O SIGMA hoje serve uma única organização (Alfa Soluções), mas [VISION_2030.md](../../VISION_2030.md) já antecipa um horizonte em que a capacidade de orquestração pode ser oferecida a mais de uma organização. Modelar o schema como se houvesse um único "dono" implícito dos dados, com a intenção de adicionar isolamento multiempresa depois, é um dos erros mais caros e mais comuns em sistemas corporativos: exige migração de dados em produção e cria risco real de vazamento de dado entre organizações durante a transição.

## Decisão

Toda tabela de domínio, desde a Release 2 — Kernel, carrega a hierarquia Tenant → Company → Workspace → User → Role (ver [MULTITENANCY.md](../../MULTITENANCY.md)) com `tenant_id` como chave estrangeira obrigatória. Nenhuma tabela nasce sem essa fronteira com a intenção de adicioná-la depois.

## Consequências

- O custo de modelar multiempresa corretamente é pago uma única vez, cedo, quando o volume de dados é zero — muito menor do que o custo de retrofitar com dados reais em produção.
- Toda query de leitura/escrita passa pelo contexto de execução resolvido pelo Kernel, com filtro de Tenant aplicado por padrão — reduz a superfície de erro humano (esquecer o filtro numa query específica).
- Adiciona uma junção/filtro a mais em praticamente toda query do sistema, mesmo enquanto só existir um Tenant real — aceito conscientemente como o preço de não precisar reescrever isso mais tarde.
- Conecta-se diretamente a [ADR-0020](0020-workspace-como-unidade-de-contexto.md): Workspace só funciona como unidade de contexto porque já existe dentro de uma hierarquia de isolamento bem definida.
