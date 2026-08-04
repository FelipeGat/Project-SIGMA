# Architecture Decision Records

Registro das decisões arquiteturais do SIGMA — o quê foi decidido, o contexto que forçou a decisão, e as consequências aceitas conscientemente. Uma ADR não é revogada por reescrita; se uma decisão muda, uma nova ADR é criada referenciando a anterior como substituída.

Novas ADRs seguem o [template.md](template.md) e são numeradas sequencialmente.

| ADR | Título |
|---|---|
| [0001](0001-repositorio-proprio-e-independente.md) | SIGMA vive em repositório próprio, independente dos sistemas que orquestra |
| [0002](0002-estrutura-de-monorepo.md) | Backend, frontend web e mobile vivem no mesmo repositório (monorepo) |
| [0003](0003-mission-como-entidade-central.md) | Mission é o agregado raiz e a entidade central do sistema |
| [0004](0004-tres-camadas-ia-agente-skill.md) | Separação em três camadas — IA (provedor), Agente (persona), Skill (capacidade) |
| [0005](0005-sigma-nunca-executa-diretamente.md) | SIGMA nunca executa diretamente — atua somente como orquestrador |
| [0006](0006-integracao-externa-e-sempre-uma-skill.md) | Toda integração externa é modelada como Skill com contrato padronizado |
| [0007](0007-comunicacao-somente-via-api.md) | Comunicação exclusivamente via API — proibido acesso direto a banco de outros sistemas |
| [0008](0008-arquitetura-orientada-a-eventos.md) | Arquitetura orientada a eventos com Redis como backbone |
| [0009](0009-stack-tecnologica-de-referencia.md) | Stack tecnológica de referência |
| [0010](0010-processo-por-epicos-com-aprovacao.md) | Desenvolvimento avança por épicos únicos, com aprovação obrigatória antes de implementar |
