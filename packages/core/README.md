# packages/core

Primitivas de domínio compartilhadas por todos os Engines: Value Objects comuns (ex: identificadores, timestamps de domínio), contratos base (interfaces que mais de um Engine implementa), exceções de domínio compartilhadas.

Não é um "utils" genérico — só pertence aqui o que é verdadeiramente compartilhado por mais de um Engine. Regra prática: se só um Engine usa, vive no pacote daquele Engine, não em `core`.

**Implementado na Release 2** — `Id` (identificador único, usado no Envelope) e `SigmaException` (exceção base de todo o SIGMA, carrega `errorCode` para o campo `error.code` do Envelope). 4 testes automatizados. Ver [ROADMAP.md](../../ROADMAP.md) e o [Decision Log da Release 2](../../docs/releases/0002-sigma-bootstrap-decision-log.md).
