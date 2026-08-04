# Skill: WhatsAppSkill

Integração com a WhatsApp Cloud API (Meta) — já em produção no ecossistema Alfa via Gestor.Alfa (`/whatsapp`, número 27 4042-4157). No SIGMA, permite que uma Mission envie/receba mensagens de WhatsApp como parte de sua execução (ex: notificar cliente, confirmar agendamento).

## Configuração

- Credenciais da Cloud API (token, phone_number_id) por empresa do ecossistema Alfa.
- Modelos de mensagem aprovados (templates) permitidos por tipo de Mission.

## Permissões

- `whatsapp.mensagem.ler` — leitura de mensagens recebidas, baixo risco.
- `whatsapp.mensagem.enviar` — escrita, exige Mission com origem rastreável; envio a um cliente (fora do time interno) exige confirmação explícita antes do disparo, por padrão.

## Entrada

Contrato específico por operação (ex: `EnviarMensagemInput{destinatario, template|texto_livre, variaveis[]}`) — detalhado no épico que implementar esta Skill.

## Saída

Confirmação de envio/entrega, ou a mensagem recebida normalizada como contexto de Mission.

## Eventos

- `whatsapp_skill.invoked`
- `whatsapp_skill.succeeded` / `whatsapp_skill.failed`
- `whatsapp_skill.message_received`

## Logs

Toda invocação registrada com Mission, Agent, destinatário e resultado, correlacionável no Audit Engine. Conteúdo de mensagem com dado sensível de negócio não logado em texto plano.

## Testes

Contrato coberto por testes automatizados contra o ambiente de homologação da Cloud API antes de qualquer uso com número de produção.

## Documentação

Diferente das demais Skills desta pasta, a integração de origem (Gestor.Alfa) já está em produção — o trabalho do épico correspondente é encapsular o que já existe no contrato de Skill do SIGMA, não construir a integração do zero. Prevista para o Épico E9 / camada L7 (ver [ROADMAP.md](../ROADMAP.md)).
