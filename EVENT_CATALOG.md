# Event Catalog

O catálogo de **todo** evento de domínio que existe no SIGMA, entre todos os Engines — não só um pedaço. Existe porque, conforme o SIGMA crescer em direção à Release 24 (ver [ROADMAP.md](ROADMAP.md)), haverá centenas de eventos; é muito mais barato manter um catálogo único desde o primeiro evento do que tentar reconstruí-lo depois. Entrega obrigatória da Release 3.5 — Architecture Consolidation.

Complementa, sem duplicar, os outros três documentos de evento do projeto:

- [EVENT_MODEL.md](EVENT_MODEL.md) — a filosofia ("tudo é evento") e a sequência **Technical** de orquestração de uma Mission entre os Engines do núcleo.
- [DOMAIN_EVENTS.md](DOMAIN_EVENTS.md) — o detalhe narrativo de cada evento de domínio por Engine (quando é publicado, o porquê de cada campo do payload, decisões de design como `RoleRevoked` ter sido adicionado por simetria). Continua sendo a referência para entender um evento; este documento aqui é a referência para **listar** todos eles de uma vez, com o que falta em `DOMAIN_EVENTS.md`: quem pode consumir, versão, contrato.
- Os `contracts/*.contract.yaml` — o campo `events:` de cada Contract deve sempre bater com a seção correspondente deste catálogo; uma validação cruzada entre os dois faz parte do processo de toda Release (ver Decision Log da Release 3.5).

## Como ler a tabela

| Coluna | Significado |
|---|---|
| Evento | Nome da classe PHP (PascalCase) |
| Bus | Nome publicado no Event Bus (dot-case) |
| Camada | Technical \| Semantic \| Business — ver [EVENT_MODEL.md](EVENT_MODEL.md#três-camadas-de-evento) |
| Publica | Qual Engine publica |
| Consome | Quem já se sabe hoje que vai consumir — "nenhum ainda" é uma resposta válida; o publicador nunca conhece o consumidor em tempo de publicação (ver [ADR-0062](docs/adr/0062-identity-nunca-conhece-outro-engine.md)), isto é só um mapa de intenção documentado |
| Versão | Versão do formato do payload deste evento especificamente — `v1` até a primeira mudança incompatível (ver [EVENT_MODEL.md — Regras](EVENT_MODEL.md#regras), regra 3) |
| Contrato | Qual `contracts/*.contract.yaml` declara este evento em seu campo `events:` |

## Identity Engine

Ver [DOMAIN_EVENTS.md#identity-engine](DOMAIN_EVENTS.md#identity-engine) para o detalhe de payload e a justificativa de cada evento.

| Evento | Bus | Camada | Publica | Consome | Versão | Contrato |
|---|---|---|---|---|---|---|
| `IdentityCreated` | `identity.created` | Semantic | Identity Engine | Memory Engine (Digital Twin de User), Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `IdentityActivated` | `identity.activated` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `IdentityDisabled` | `identity.disabled` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `SessionStarted` | `session.started` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `SessionEnded` | `session.ended` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `WorkspaceSelected` | `workspace.selected` | Semantic | Identity Engine | Memory Engine (contexto do Digital Twin) | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `RoleAssigned` | `role.assigned` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `RoleRevoked` | `role.revoked` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `PermissionGranted` | `permission.granted` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |
| `PermissionRevoked` | `permission.revoked` | Semantic | Identity Engine | Audit Engine | v1 | [Identity.contract.yaml](contracts/Identity.contract.yaml) |

Nenhum destes é publicado de fato no `IEventBus` ainda além do que a `Application/` do Identity Engine já faz (ver `packages/identity-engine/src/Application/UseCase/`) — "Consome" acima é intenção documentada para quando Memory/Audit Engine existirem (Release 4 em diante), não implementação já existente do lado consumidor.

## Kernel / Bootstrap (infraestrutura, fora da camada Semantic)

Nenhum evento de domínio — Release 2 é infraestrutura pura ([ADR-0053](docs/adr/0053-escopo-restrito-release-2.md)). A sequência Technical de orquestração de Mission (`MissionRequested`, `IntentDetected`, etc.) já está catalogada em [EVENT_MODEL.md](EVENT_MODEL.md#catálogo-de-eventos-canônico) — não duplicada aqui porque nasce com Releases futuras (Intent/Planner/Mission Engine), nenhuma delas implementada ainda.

## Memory Engine

Ver [DOMAIN_EVENTS.md#memory-engine](DOMAIN_EVENTS.md#memory-engine) — catalogados antes do código, junto do [MEMORY_MODEL.md](MEMORY_MODEL.md)/[MEMORY_LIFECYCLE.md](MEMORY_LIFECYCLE.md)/[MEMORY_PROMOTION_RULES.md](MEMORY_PROMOTION_RULES.md), mesmo padrão usado para Identity antes da Release 3A. Revisão 2 do modelo (ver [ADR-0082](docs/adr/0082-processo-oficial-de-desenvolvimento-de-engines.md) a [ADR-0088](docs/adr/0088-retracao-expiracao-e-governanca-de-promocao.md)) acrescentou cinco eventos.

| Evento | Bus | Camada | Publica | Consome | Versão | Contrato |
|---|---|---|---|---|---|---|
| `ContextMemoryStarted` | `context_memory.started` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `ContextMemoryClosed` | `context_memory.closed` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `MemoryRecordObserved` | `memory.record_observed` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` (a nascer com a Release 4) |
| `MemoryPromoted` | `memory.promoted` | Semantic | Memory Engine | Audit Engine, curadoria humana de Knowledge (`toLevel: LongTerm`) | v1 | `Memory.contract.yaml` |
| `MemoryDeprecated` | `memory.deprecated` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `MemoryRetracted` | `memory.retracted` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `MemorySubjectPinned` | `memory.subject_pinned` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `KnowledgeRecordIndexed` | `knowledge.indexed` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `DigitalTwinCreated` | `digital_twin.created` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `DigitalTwinUpdated` | `digital_twin.updated` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |
| `DigitalTwinStale` | `digital_twin.stale` | Semantic | Memory Engine | Audit Engine | v1 | `Memory.contract.yaml` |

## Mission / Planner / Intent / Skill / Agent / Execution / Audit Engine

Nenhum evento ainda — nenhum destes Engines tem Proposal aprovada nem código. Cada um ganha sua seção aqui quando a modelagem de domínio correspondente for escrita (mesmo momento em que ganhou uma seção o Identity Engine, antes da Release 3A, e o Memory Engine, antes da Release 4A) — nunca eventos hipotéticos sem nenhum plano de implementação próximo.

## Processo

1. Todo evento novo é catalogado aqui e em [DOMAIN_EVENTS.md](DOMAIN_EVENTS.md) junto da modelagem de domínio do Engine que o publica — antes do código, não depois (mesmo padrão de [IDENTITY_MODEL.md](IDENTITY_MODEL.md)/[DOMAIN_EVENTS.md](DOMAIN_EVENTS.md) antes da Release 3A, e [MEMORY_MODEL.md](MEMORY_MODEL.md)/[DOMAIN_EVENTS.md](DOMAIN_EVENTS.md) antes da Release 4A). Um evento só entra aqui quando a Release que o publica já tem uma Proposal em preparação — nunca especulativo sem plano de implementação.
2. Ao publicar um evento novo, a linha correspondente entra nesta tabela **e** no campo `events:` do Contract correspondente, no mesmo Pull Request — as duas fontes nunca divergem por mais que um PR.
3. Uma mudança incompatível de payload sobe a versão (`v1` → `v2`) e o evento antigo continua existindo até todo consumidor migrar — nunca uma edição silenciosa da mesma versão.
