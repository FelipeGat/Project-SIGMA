# Skills

Documentação — ainda sem código — de cada Skill planejada. Uma Skill é uma capacidade de ação concreta, geralmente uma integração com um sistema externo, invocável por um Agent através do **Skill Engine** (ver [DOMAIN.md](../DOMAIN.md) e [ADR-0006](../docs/adr/0006-integracao-externa-e-sempre-uma-skill.md)).

Toda Skill, ao ser implementada, segue o mesmo contrato ([docs/architecture/ARCHITECTURE.md §6](../docs/architecture/ARCHITECTURE.md)): Configuração, Permissões, Entrada, Saída, Eventos, Logs, Testes, Documentação. Os arquivos desta pasta documentam esse contrato antes de qualquer linha de código — servem tanto para revisão da proposta quanto de especificação para o épico que a implementar.

| Skill | Sistema externo | Status |
|---|---|---|
| [gestor.md](gestor.md) | Gestor.Alfa | Especificada |
| [github.md](github.md) | GitHub | Especificada |
| [telegram.md](telegram.md) | Telegram | Especificada |
| [calendar.md](calendar.md) | Google Calendar | Especificada |
| [email.md](email.md) | E-mail (SMTP/IMAP) | Especificada |

Nenhuma Skill acessa banco de dados diretamente — toda comunicação com o sistema externo é via API pública dele. Ver [ADR-0007](../docs/adr/0007-comunicacao-somente-via-api.md).
