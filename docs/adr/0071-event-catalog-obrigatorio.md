# ADR-0071: `EVENT_CATALOG.md` como catálogo obrigatório de todo evento do SIGMA

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

[DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) já cataloga os eventos de um Engine por vez (hoje só Identity), mas não tem colunas para quem consome, versão do payload, ou qual Contract declara cada evento. O Product Owner apontou que, na Release 24, o SIGMA terá centenas de eventos entre dezenas de Engines — sem um catálogo único e completo desde já, reconstruir essa visão depois exigiria vasculhar código de todo Engine.

## Decisão

`EVENT_CATALOG.md` (raiz do repositório) é o catálogo único de **todo** evento de domínio do SIGMA, com uma seção por Engine e as colunas Evento/Bus/Camada/Publica/Consome/Versão/Contrato. Não duplica [DOMAIN_EVENTS.md](../../DOMAIN_EVENTS.md) (que continua sendo a referência narrativa do porquê de cada evento existir) — as duas tabelas precisam bater; divergência é bug de documentação. Todo evento novo, de qualquer Engine futuro, entra nesta tabela no mesmo Pull Request que o publica, e no campo `events:` do Contract correspondente — nunca depois.

## Consequências

- Uma pergunta como "quem consome `IdentityDisabled`?" tem resposta em um lugar só, sem precisar ler código de Engines que talvez nem existam ainda.
- Mais um documento para manter atualizado a cada evento novo — aceito porque o custo de reconstruir isso retroativamente na Release 12+ seria muito maior.
- `contracts/*.contract.yaml` e `EVENT_CATALOG.md` agora têm uma obrigação explícita de consistência mútua — parte da validação cruzada de toda Release a partir de agora (ver [ADR-0070](0070-roadmap-estendido-24-releases.md) e o Decision Log da Release 3.5).
