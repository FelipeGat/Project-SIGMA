# Skill: EmailSkill

Integração de envio/leitura de e-mail (SMTP/IMAP ou provedor equivalente) — usada para comunicação formal com clientes ou registro de Document por e-mail.

## Configuração

- Credenciais da conta/domínio de envio autorizado por empresa do ecossistema Alfa.
- Modelos de assunto/corpo permitidos por tipo de Mission (evita e-mail com formato livre não revisado saindo em nome da empresa).

## Permissões

- `email.ler` — leitura de caixa de entrada dedicada ao SIGMA, quando aplicável.
- `email.enviar` — escrita, exige Mission com origem rastreável; envio a destinatário externo (cliente) exige confirmação explícita antes do disparo, por padrão.

## Entrada

Contrato específico por operação (ex: `EnviarEmailInput{destinatario, assunto, corpo, anexos[]}`) — detalhado no épico que implementar esta Skill.

## Saída

Confirmação de envio/entrega, ou o e-mail normalizado como `Document`/contexto de Mission quando lido.

## Eventos

- `email_skill.invoked`
- `email_skill.succeeded` / `email_skill.failed`

## Logs

Toda invocação registrada com Mission, Agent, destinatário e resultado, correlacionável no Audit Engine. Corpo de e-mails com dado sensível de negócio armazenado conforme política de retenção a definir no épico de implementação.

## Testes

Contrato coberto por testes automatizados contra uma caixa de teste antes de qualquer uso com destinatários reais.

## Documentação

Prevista para o Épico E9 — Expansão de Skills (ver [ROADMAP.md](../ROADMAP.md)).
