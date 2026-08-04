# services/scheduler

Execução de tarefas recorrentes do Kernel: promoção de Memory entre níveis (ver [MEMORY_ARCHITECTURE.md](../../MEMORY_ARCHITECTURE.md)), verificação periódica de regras do Automation Engine, limpeza de Operational Memory expirada.

Vazio na Fase Foundation. Nasce quando o primeiro caso de uso recorrente existir — não antes, para não construir agendamento sem nada relevante para agendar.
