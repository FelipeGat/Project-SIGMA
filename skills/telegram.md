# Skill: TelegramSkill

Integração com a API de Bot do Telegram — canal de notificação e interação leve com Users (ex: alerta de Mission concluída/falha, aprovação rápida de uma etapa sensível).

## Configuração

- Token do Bot do Telegram dedicado ao SIGMA.
- Mapeamento entre User/Team do SIGMA e chat_id do Telegram correspondente.

## Permissões

- `telegram.mensagem.enviar` — escrita, baixo risco por padrão (notificação), mas nunca envia a um destinatário fora do mapeamento configurado.
- `telegram.mensagem.ler` (respostas/comandos do usuário) — usada para interação (ex: aprovar/rejeitar uma etapa de Mission por resposta no chat).

## Entrada

Contrato específico por operação (ex: `EnviarMensagemInput{user_id, texto, botoes_de_acao[]}`) — detalhado no épico que implementar esta Skill.

## Saída

Confirmação de envio, e — quando aplicável — a resposta/ação do usuário capturada de volta como evento.

## Eventos

- `telegram_skill.invoked`
- `telegram_skill.succeeded` / `telegram_skill.failed`
- `telegram_skill.user_responded` (quando a mensagem esperava interação)

## Logs

Toda invocação registrada com Mission, Agent, destinatário e resultado, correlacionável no Audit Engine. Conteúdo de mensagens com dado sensível de negócio não é logado em texto plano.

## Testes

Contrato coberto por testes automatizados contra um Bot e chat de teste antes de qualquer uso com Users reais.

## Documentação

Prevista para o Épico E9 — Expansão de Skills (ver [ROADMAP.md](../ROADMAP.md)).
