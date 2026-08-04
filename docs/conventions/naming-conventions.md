# Convenções de Nomenclatura

Aplica-se a todo o monorepo (`apps/`, `packages/`, `services/`, `plugins/`, `docs/`). O objetivo é que o nome de qualquer coisa no sistema já entregue seu papel no domínio — ver [ARCHITECTURE.md](../architecture/ARCHITECTURE.md).

## Domínio (linguagem ubíqua)

Os termos abaixo têm significado único e fixo em todo o sistema — código, documentação, commits e conversa. Não usar sinônimos.

| Termo | Significa | Nunca usar como sinônimo de |
|---|---|---|
| **Mission** | A solicitação central que o sistema interpreta, planeja e executa | "Task", "Job", "Request" isolados |
| **Plan** | O planejamento gerado para uma Mission | — |
| **Subtask** | Uma unidade de trabalho dentro do Plan de uma Mission | "Mission" |
| **Agent** | Uma persona operacional especializada que usa uma IA | "IA", "Skill" |
| **IA** (AIProvider) | O provedor/modelo bruto (Claude, ChatGPT, Gemini, Manus) | "Agent" |
| **Skill** | Uma capacidade de ação concreta (integração) invocável por um Agent — o conceito de domínio | "Integration" isolado |
| **Plugin** | O empacotamento técnico de uma Skill, carregado dinamicamente (ver [PLUGIN_SYSTEM.md](../../PLUGIN_SYSTEM.md)) | "Skill" — Plugin é a implementação; Skill é o conceito |
| **Workspace** | Unidade de contexto operacional (ex: um cliente) que agrega Client/Project/Budget/Meeting relacionados | "Project" isolado |
| **Tenant** | Fronteira de isolamento total de dados | "Company" |
| **Knowledge** | O que o sistema sabe | "Memory" |
| **Memory** | O que o sistema aprendeu | "Knowledge" |
| **Event** | Um fato de domínio publicado no Event Bus | "Log" |
| **Log** | Um registro de execução/auditoria | "Event" |
| **Automation** | Uma regra declarativa que reage a Events | — |
| **Process** | Um fluxo/procedimento reutilizável que uma Mission pode seguir | "Automation" |

## Monorepo (apps/packages/services/plugins)

| Elemento | Convenção | Exemplo |
|---|---|---|
| Pacote (`packages/`) | kebab-case | `mission-engine`, `design-system` |
| Serviço (`services/`) | kebab-case | `ai-router`, `event-bus` |
| App (`apps/`) | kebab-case | `web`, `mobile`, `admin` |
| Plugin (`plugins/`) | kebab-case, mesmo nome da Skill em minúsculas | `gestor`, `github`, `whatsapp` |
| `manifest.json` de Plugin | campos em snake_case (ver [plugins/manifest.schema.json](../../plugins/manifest.schema.json)) | `api_base_url`, `requires_human_approval` |

## Backend (Laravel / PHP 8.4, dentro de cada pacote)

| Elemento | Convenção | Exemplo |
|---|---|---|
| Pacote de Engine | kebab-case (diretório), PascalCase singular (namespace interno) | `packages/mission-engine/` |
| Entidade de domínio | PascalCase, singular | `Mission`, `Skill`, `Agent` |
| Value Object | PascalCase, sufixo não obrigatório mas descritivo | `MissionStatus`, `SkillInput` |
| DTO | PascalCase + sufixo `DTO` | `CreateMissionDTO` |
| Action | Verbo no infinitivo + PascalCase | `CreateMissionAction`, `InterpretMissionAction` |
| Domain Event | Verbo no particípio passado + PascalCase | `MissionCreated`, `MissionCompleted`, `SubtasksCreated` |
| Listener | PascalCase + sufixo `Listener` | `NotifyAutomationOnMissionCompletedListener` |
| Repository (contrato) | PascalCase + sufixo `Repository` (interface, em `Domain/Repositories`) | `MissionRepository` |
| Repository (implementação) | PascalCase + sufixo `EloquentRepository` (em `Infrastructure/Repositories`) | `MissionEloquentRepository` |
| Policy | PascalCase + sufixo `Policy` | `MissionPolicy` |
| Tabela de banco | snake_case, plural | `missions`, `mission_subtasks` |
| Coluna de banco | snake_case | `mission_status`, `created_by_user_id` |
| Rota de API | kebab-case, plural, prefixada pelo contexto | `POST /api/missions`, `GET /api/skills/{skill}/logs` |
| Fila (queue) | kebab-case | `missions-execution`, `skills-invocation` |
| Nome de Skill concreta | PascalCase + sufixo `Skill` | `GestorSkill`, `WhatsAppSkill`, `GitHubSkill` |

## Frontend (React / TypeScript / React Native)

| Elemento | Convenção | Exemplo |
|---|---|---|
| Componente | PascalCase | `MissionTimeline.tsx` |
| Hook | camelCase, prefixo `use` | `useMissionStatus.ts` |
| Store/estado global | camelCase + sufixo `Store` ou `Slice` | `missionStore.ts` |
| Tipo/Interface TS | PascalCase | `Mission`, `SkillInput` |
| Arquivo de rota/página | kebab-case ou PascalCase conforme roteador escolhido no épico correspondente | — |
| Token de Design System | kebab-case, prefixado por categoria | `color-accent-primary`, `space-4` |

## Eventos de domínio (nome + versionamento)

Formato: `<Contexto>.<Entidade><ParticípioPassado>` no Event Bus, `<Entidade><ParticípioPassado>` na classe PHP.

- Classe PHP: `MissionCompleted`
- Nome publicado no Event Bus: `mission.completed`
- Alterações incompatíveis no payload de um evento exigem novo nome versionado (`mission.completed.v2`), nunca alteração silenciosa do formato de um evento já publicado.

## Git

- Branches: `epic/<camada>-<slug>` (ex: `epic/l1-kernel`, `epic/l4-mission-engine` — camadas em [ROADMAP.md](../../ROADMAP.md)), `fix/<slug>`, `docs/<slug>`.
- Commits: [Conventional Commits](https://www.conventionalcommits.org/) — `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`. Detalhado em [CONTRIBUTING.md](../../CONTRIBUTING.md).
- ADRs: `docs/adr/NNNN-slug-em-kebab-case.md`, numeração sequencial de 4 dígitos.
