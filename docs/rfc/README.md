# RFC

Onde uma ideia é discutida **antes** de virar decisão. Diferença para ADR: um RFC não é uma decisão — é uma proposta aberta a discussão, que pode ser aceita, rejeitada ou revisada. Só depois de aceito um RFC gera uma ADR (a decisão registrada) e, então, código. Ver [ADR-0051](../adr/0051-processo-rfc.md).

```
RFC → Discussão → Aprovação → ADR → Código
```

Use um RFC quando a ideia ainda não está madura o suficiente para ser proposta como decisão — quando existem alternativas genuinamente abertas e vale a pena registrar o raciocínio de descarte. Não use RFC para decisões já claras dentro do escopo de uma Release aprovada — isso é Decision Log (ver [ADR-0047](../adr/0047-decision-log-por-release.md)).

Novo RFC: copie [template.md](template.md), numere sequencialmente (`NNNN-slug.md`).
