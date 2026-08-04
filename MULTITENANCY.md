# Multitenancy

## O princípio

Mesmo sendo, hoje, um sistema interno de uso único (a própria Alfa Soluções), o SIGMA é multiempresa desde o primeiro schema de banco — nunca retrofitado. Ver [ADR-0021](docs/adr/0021-multitenancy-desde-o-schema.md) e [VISION_2030.md](VISION_2030.md), que já antecipa o SIGMA sendo usado por mais de uma organização.

Retrofitar isolamento multiempresa depois que dados já existem sem essa fronteira é um dos erros mais caros e mais comuns em sistemas corporativos — exige migração de dados em produção, reescrita de toda query, e período de risco real de vazamento de dado entre empresas. O custo de fazer certo desde o início é pequeno comparado a isso.

## A hierarquia

```
Tenant
└── Company
    └── Workspace
        └── User (com Role)
```

| Nível | O que representa | Exemplo hoje |
|---|---|---|
| **Tenant** | Fronteira de isolamento total de dados — o nível mais alto | "Alfa Soluções" (tenant único hoje; um segundo tenant só existiria se o SIGMA fosse oferecido white-label a outra organização) |
| **Company** | Uma empresa dentro do tenant | GW, Delta, Invest — as empresas do Grupo Soluções, já existentes como conceito multiempresa no Gestor.Alfa |
| **Workspace** | Um contexto operacional dentro de uma Company (ver [WORKSPACES.md](WORKSPACES.md)) | "Cliente Brenno" |
| **User** | Uma pessoa, associada a um Tenant, membro de um ou mais Workspaces | Um comercial da Invest |
| **Role** | Um conjunto de permissões, aplicável no nível Tenant, Company ou Workspace | "Comercial", "Técnico", "Administrativo" |

## Regra desde o schema

Toda tabela de domínio criada a partir da camada L1 — Kernel carrega `tenant_id` como chave estrangeira obrigatória (não nula), e `company_id`/`workspace_id` quando aplicável ao domínio da tabela. Nenhuma tabela nasce "genérica" com a intenção de adicionar isolamento depois.

Toda query de leitura/escrita passa pelo contexto de execução resolvido pelo [Kernel](KERNEL.md) — nenhum Engine ou Plugin monta uma query sem o filtro de Tenant já aplicado por padrão.

## Relação com sistemas externos

O SIGMA não duplica a modelagem multiempresa de cada sistema integrado (ex: `empresa_id` no Gestor.Alfa) — mapeia Company do SIGMA para o identificador de empresa correspondente em cada sistema externo, via configuração de Skill/Plugin (ver `empresa_ids` no manifest de [plugins/gestor](plugins/gestor/manifest.json)).

## Onde vive

Fundação em `services/auth` e no schema base do [Kernel](KERNEL.md) — camada L1 do [ROADMAP.md](ROADMAP.md).
