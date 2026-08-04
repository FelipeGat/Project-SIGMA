# services

Processos deployáveis de forma independente — cada um monta um ou mais pacotes de [/packages](../packages/) numa aplicação executável (HTTP, worker, scheduler). Diferença em relação a `packages/`: um pacote é uma biblioteca sem ciclo de vida próprio; um serviço é algo que roda, tem processo, tem deploy, tem log de uptime.

| Serviço | Responsabilidade |
|---|---|
| [gateway/](gateway/) | Superfície HTTP/WebSocket pública do SIGMA — onde `apps/*` e `packages/sdk` entram. Monta os Engines para atender requisições |
| [auth/](auth/) | Autenticação e autorização — User, Team, Company, e a hierarquia Tenant/Workspace/Role (ver [MULTITENANCY.md](../MULTITENANCY.md)) |
| [scheduler/](scheduler/) | Agendamento e execução de tarefas recorrentes do Kernel (ex: promoção de Memory entre níveis, verificação de Automations) |
| [notifications/](notifications/) | Orquestra envio de notificação ao usuário (independente do canal — usa Plugins de comunicação) |
| [ai-router/](ai-router/) | Camada técnica de comunicação com cada provedor de IA (rate limit, retry, custo) — consumida pelo `agent-engine`, nunca pelo domínio diretamente |
| [event-bus/](event-bus/) | Wrapper do backbone de eventos (Redis) — publicação/assinatura usada por todos os Engines |

Nenhum serviço acessa o banco de dados de outro sistema Alfa diretamente — comunicação externa acontece via Plugin, no `skill-engine`. Ver [ADR-0007](../docs/adr/0007-comunicacao-somente-via-api.md).
