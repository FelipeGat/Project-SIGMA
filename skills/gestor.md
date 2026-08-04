# Skill: GestorSkill

Integração com o Gestor.Alfa — sistema de gestão comercial/financeira/operacional já em produção no ecossistema Alfa (orçamentos, clientes, obras, funil de vendas, financeiro).

## Configuração

- URL base da API do Gestor.Alfa por ambiente (produção/homologação).
- Credenciais de serviço (token de API dedicado ao SIGMA, não credencial de usuário humano).
- Empresa(s) do Gestor.Alfa às quais esta instância da Skill tem acesso (multiempresa).

## Permissões

Concedida por Mission/Agent conforme a Subtask. Granularidade mínima esperada:

- `gestor.clientes.ler`, `gestor.orcamentos.ler`, `gestor.funil.ler` — leitura, baixo risco.
- `gestor.orcamentos.criar`, `gestor.funil.atualizar` — escrita, exige Mission com origem rastreável e, tipicamente, validação humana antes de efetivar.
- Nunca concedida: acesso direto ao banco do Gestor.Alfa (ver [ADR-0007](../docs/adr/0007-comunicacao-somente-via-api.md)).

## Entrada

Contrato específico por operação (ex: `BuscarClienteInput{documento|nome}`, `CriarOrcamentoInput{cliente_id, itens[]}`) — detalhado no épico que implementar esta Skill.

## Saída

Dados normalizados do Gestor.Alfa no formato de domínio do SIGMA (ex: um `Client`, um `Budget` — ver [DOMAIN.md](../DOMAIN.md)), nunca o payload cru da API externa repassado sem tradução.

## Eventos

- `gestor_skill.invoked`
- `gestor_skill.succeeded` / `gestor_skill.failed`
- Eventos específicos de domínio quando aplicável (ex: `budget.created_via_gestor`)

## Logs

Toda invocação registrada com Mission, Agent, operação, tempo de resposta e resultado (sucesso/falha), correlacionável no Audit Engine.

## Testes

Contrato coberto por testes automatizados contra um ambiente de homologação do Gestor.Alfa antes de qualquer uso em produção; casos de falha de rede/API externa fora do ar cobertos explicitamente.

## Documentação

Candidata a primeira Skill real do SIGMA (ver Épico E3 em [ROADMAP.md](../ROADMAP.md)), por já existir API e caso de uso concreto no ecossistema Alfa.
