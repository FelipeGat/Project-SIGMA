# Playbook: Nova Reunião

## Gatilho

"Sigma, participe da reunião do cliente X" — ou um Meeting já agendado no calendário de um Team que o SIGMA está autorizado a acompanhar.

## Contexto necessário

- Identidade do cliente/Project envolvido e seu histórico relevante (`knowledge/clientes/`).
- Pauta ou objetivo da reunião, quando informado explicitamente pelo autor da Mission.
- Participantes esperados (internos e externos).

## Fases esperadas

1. Confirmar horário/link da reunião (via `GoogleCalendarSkill`, se ainda não agendada).
2. Reunir contexto prévio relevante (Knowledge do cliente, Missions/orçamentos em aberto relacionados).
3. Acompanhar/registrar a reunião (participação depende de qual Agent e Skill de captura estiverem disponíveis no épico correspondente — não presumido no Sprint 0.1).
4. Consolidar em Document (ata) — Agent de Documentação (Manus).
5. Propor próximos passos (nova Mission, se aplicável) para aprovação humana.

## Agentes e Skills tipicamente envolvidos

- Manus (Documentação) — ata e consolidação.
- ChatGPT (Estratégia) — quando a reunião é comercial e exige leitura de contexto de negociação.
- `GoogleCalendarSkill`, `GestorSkill` (contexto do cliente/orçamento).

## Pontos de decisão humana

- Qualquer compromisso assumido em nome da empresa durante a reunião não é registrado como decisão automática — é proposta, sujeita a validação de quem participou.

## Critérios de sucesso

Ata registrada e vinculada ao cliente/Project correto; próximos passos identificados e, se aplicável, uma nova Mission proposta — não necessariamente criada sem aprovação.

## Conhecimento relacionado

[knowledge/clientes/](../knowledge/clientes/), [skills/calendar.md](../skills/calendar.md), [agents/manus.md](../agents/manus.md).
