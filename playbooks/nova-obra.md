# Playbook: Nova Obra

## Gatilho

Um orçamento de obra é aprovado — ou uma Mission explícita ("Sigma, abra a obra do cliente X").

## Contexto necessário

- Orçamento aprovado de origem (escopo, etapas, valores).
- Equipe técnica/responsável disponível.
- Particularidades do cliente/local já registradas em Knowledge.

## Fases esperadas

1. Criar a obra no módulo de Gestão de Obras do Gestor.Alfa a partir do orçamento aprovado, com etapas e peso/% correspondentes.
2. Agendar marcos relevantes (início, vistorias) via `GoogleCalendarSkill`.
3. Notificar Team técnico responsável.
4. Acompanhar ao longo da execução: fotos (antes/andamento/depois), ocorrências — ponto de integração com Missions subsequentes de acompanhamento, não coberto em detalhe por este Playbook inicial.

## Agentes e Skills tipicamente envolvidos

- ChatGPT (Estratégia) — planejamento de etapas.
- Manus (Documentação) — relatório de obra.
- `GestorSkill`, `GoogleCalendarSkill`, `TelegramSkill` (notificação de equipe).

## Pontos de decisão humana

Abertura formal da obra e qualquer alteração de escopo/prazo em relação ao orçamento original exigem confirmação humana.

## Critérios de sucesso

Obra criada no Gestor.Alfa com etapas fiéis ao orçamento aprovado, equipe notificada, marcos agendados.

## Conhecimento relacionado

[skills/gestor.md](../skills/gestor.md), [skills/calendar.md](../skills/calendar.md), [knowledge/processos/](../knowledge/processos/).
