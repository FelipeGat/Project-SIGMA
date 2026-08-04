# packages/core

Primitivas de domínio compartilhadas por todos os Engines: Value Objects comuns (ex: identificadores, timestamps de domínio), contratos base (interfaces que mais de um Engine implementa), exceções de domínio compartilhadas.

Não é um "utils" genérico — só pertence aqui o que é verdadeiramente compartilhado por mais de um Engine. Regra prática: se só um Engine usa, vive no pacote daquele Engine, não em `core`.

Vazio na Fase Foundation. Nasce junto com o primeiro pacote que precisar dele — provavelmente `kernel` (Release 2 do [ROADMAP.md](../../ROADMAP.md)).
