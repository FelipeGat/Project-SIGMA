# Digital Twin

Nenhum Engine ou Capability lê um sistema externo diretamente a cada chamada. Ele lê uma representação viva e sincronizada desse objeto, mantida pelo SIGMA: o **Digital Twin**. Ver [SIGMA_PROTOCOL.md §6](SIGMA_PROTOCOL.md#6-digital-twin) e [ADR-0035](docs/adr/0035-digital-twin.md).

```
Client (Sea Master) → ClientTwin (SIGMA) → Gestor.Alfa (fonte da verdade)
```

## O que tem Digital Twin

Client, Project, Company e User (ver [DOMAIN.md](DOMAIN.md)) — as entidades de negócio que o SIGMA referencia com frequência para dar contexto a uma Mission, e cuja fonte da verdade vive em um sistema externo (tipicamente via `GestorSkill`).

## Por que não ler o sistema externo direto a cada vez

- **Contexto**: o Planner e os Agents precisam de contexto de negócio rapidamente, a cada Subtask — uma chamada de API externa a cada leitura tornaria o sistema lento e frágil a instabilidade do sistema de origem.
- **Cache com significado**: um Digital Twin não é um cache genérico (TTL cego) — é atualizado deliberadamente a partir de Semantic Events (ver [EVENT_MODEL.md](EVENT_MODEL.md)), então reflete o que o SIGMA sabe *por ter causado ou observado* uma mudança, não uma cópia que pode estar arbitrariamente desatualizada.
- **Auditoria**: o estado do Twin em qualquer momento no passado é reconstruível a partir do histórico de eventos que o atualizaram — o que uma chamada direta e sem estado ao sistema externo nunca ofereceria.
- **IA**: um Agent raciocinando sobre uma Subtask consulta o Twin (rápido, estruturado, já no vocabulário do domínio SIGMA) em vez de ter que entender o formato nativo de cada API externa.

## Como um Digital Twin é atualizado

1. Na criação/primeira necessidade: uma Capability de leitura (ex: `GestorSkill.FindClient`) popula o Twin a partir do sistema de origem.
2. Em toda escrita: quando uma Capability de escrita é executada (ex: `CreateBudget`), o Semantic Event resultante (`budget.created_via_gestor`) atualiza o Twin correspondente.
3. Refresh periódico (via `services/scheduler`) para entidades que podem mudar fora do SIGMA, sem uma Capability do SIGMA ter causado a mudança — frequência e escopo definidos no épico de implementação, não nesta especificação.

Um Twin desatualizado (fora da janela de refresh esperada) produz um `warning` no [Envelope](SIGMA_PROTOCOL.md#1-o-envelope) de qualquer resposta que o utilize — nunca falha silenciosamente como se estivesse correto.

## O que um Digital Twin nunca é

- **Nunca é a fonte da verdade.** Toda escrita de negócio passa pelo Skill Engine → Plugin → API externa (ver [ADR-0007](docs/adr/0007-comunicacao-somente-via-api.md)) — o Twin é atualizado como consequência, nunca escrito diretamente por um Agent ou Engine.
- **Não substitui o Workspace.** [Workspace](WORKSPACES.md) é o agrupamento contextual de vários Twins e Missions relacionados a uma situação; um Digital Twin é a representação de **uma** entidade específica.
- **Não é Knowledge nem Memory** em sentido pleno — é factual e volátil (reflete o estado atual de um sistema externo), enquanto Knowledge é curado e Memory é experiencial (ver [MEMORY_ARCHITECTURE.md](MEMORY_ARCHITECTURE.md)). Custodiado pelo Memory Engine por afinidade operacional, mas conceitualmente distinto.

## Onde vive

Persistência e atualização de Digital Twins ficam sob custódia do **Memory Engine** (Release 4) — é o Engine já responsável por "o que o SIGMA sabe sobre o mundo". O primeiro Twin populado de fato é o `UserTwin`, já na Release 4, a partir dos eventos que o Identity Engine já publica hoje — não precisa esperar por nenhuma Capability externa (ver [ADR-0079](docs/adr/0079-usertwin-desde-a-release-4.md)). `ClientTwin`/`ProjectTwin`/`CompanyTwin` têm o mesmo mecanismo pronto desde a Release 4, mas só ganham instâncias reais na Release 8 — Skill Engine, quando a primeira Capability de leitura real (`GestorSkill.FindClient`) existir. Ver [MEMORY_MODEL.md](MEMORY_MODEL.md) para o modelo completo.
