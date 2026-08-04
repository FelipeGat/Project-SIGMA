# ADR-0044: Configuration Provider — cada Module declara sua própria configuração

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Uma função `config()` global, lida de qualquer lugar do código, é o padrão mais simples de configuração — e também o que mais facilmente vira uma fonte de acoplamento invisível: qualquer Module pode ler qualquer chave de configuração, mesmo uma que pertence a outro Module, sem que isso apareça em nenhum contrato explícito.

## Decisão

Segue-se o padrão de **Configuration Provider** (como em Spring Boot): cada Module declara, via `config()` (ver contrato `Module` em [BOOTSTRAP.md](../../BOOTSTRAP.md)), o schema da configuração que precisa — chaves obrigatórias, opcionais, valores default. O Configuration Provider central resolve essas declarações contra o ambiente e falha explicitamente no `boot` se algo obrigatório faltar. Nenhum Module lê variável de ambiente diretamente.

## Consequências

- A configuração que um Module precisa é visível e auditável a partir do próprio Module — não é preciso ler o código inteiro para saber do que ele depende.
- Falha de configuração é detectada no `boot`, com mensagem explícita de qual Module e qual chave — nunca um valor `null`/`undefined` se propagando silenciosamente até quebrar algo, muito depois, num ponto distante da causa real.
- Um Module nunca acessa a configuração de outro — se dois Modules precisam do mesmo valor, cada um declara sua própria necessidade (mesmo que resolvida para o mesmo valor de ambiente por trás), preservando o desacoplamento.
- Implementação nasce com a Release 2 — SIGMA Bootstrap.
