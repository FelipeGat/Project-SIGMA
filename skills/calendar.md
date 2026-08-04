# Skill: GoogleCalendarSkill

Integração com o Google Calendar — usada para criar/consultar Meetings e para Subtasks que dependem de disponibilidade de agenda.

## Configuração

- Credenciais OAuth de serviço (conta de calendário dedicada ao SIGMA ou delegação por domínio Google Workspace).
- Calendários aos quais esta instância da Skill tem acesso.

## Permissões

- `calendar.eventos.ler` — leitura de disponibilidade e detalhes de Meeting, baixo risco.
- `calendar.eventos.criar`, `calendar.eventos.atualizar` — escrita, exige Mission com origem rastreável; convites a participantes externos exigem confirmação explícita antes do envio.

## Entrada

Contrato específico por operação (ex: `CriarEventoInput{titulo, participantes[], inicio, fim, descricao}`) — detalhado no épico que implementar esta Skill.

## Saída

Dados normalizados de um `Meeting` (ver [DOMAIN.md](../DOMAIN.md)): identificador, horário, participantes, link de acesso.

## Eventos

- `calendar_skill.invoked`
- `calendar_skill.succeeded` / `calendar_skill.failed`
- `meeting.scheduled` (evento de domínio, não só técnico da Skill)

## Logs

Toda invocação registrada com Mission, Agent, evento afetado e resultado, correlacionável no Audit Engine.

## Testes

Contrato coberto por testes automatizados contra um calendário de teste antes de qualquer uso com agendas reais.

## Documentação

Prevista para o Épico E9 — Expansão de Skills (ver [ROADMAP.md](../ROADMAP.md)); pode ser antecipada se um Playbook de reunião (ver [/playbooks](../playbooks/)) entrar em execução real antes disso.
