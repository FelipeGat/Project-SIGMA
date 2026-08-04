# backend/

Reservado para a API do SIGMA — Laravel 12 / PHP 8.4.

Vazio propositalmente durante a Fase Foundation (Sprint 0). Nenhum código de aplicação é criado até que o Épico E1 — Mission Engine seja apresentado e aprovado (ver [ROADMAP.md](../ROADMAP.md)).

Quando o E1 iniciar, a estrutura de módulos seguirá o padrão descrito em [docs/architecture/ARCHITECTURE.md §7](../docs/architecture/ARCHITECTURE.md#7-estrutura-de-módulo-backend):

```
backend/app/Modules/<Contexto>/
├── Domain/{Entities,ValueObjects,Events,Repositories}
├── Application/{Actions,Services,DTOs}
├── Infrastructure/{Repositories,Listeners,Observers}
└── Presentation/{Controllers,Policies,Resources}
```
