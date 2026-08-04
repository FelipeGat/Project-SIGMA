# services/notifications

Orquestra o envio de notificações a Users — independente do canal final (e-mail, Telegram, WhatsApp). Decide *que* uma notificação deve sair e para quem; o envio de fato acontece através do Plugin do canal correspondente (ver [/plugins](../../plugins/)), nunca diretamente.

Vazio na Fase Foundation. Primeiro consumidor provável: eventos do Mission Engine (`mission.completed`, `mission.failed`) precisando alertar um User.
