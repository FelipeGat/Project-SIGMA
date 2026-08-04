# ADR-0027: Capability é a unidade de implementação de uma Skill — não a função

- **Status**: Aceito — refina [ADR-0006](0006-integracao-externa-e-sempre-uma-skill.md) e [ADR-0017](0017-plugin-system.md)
- **Data**: 2026-08-04

## Contexto

Até aqui, o contrato de uma Skill/Plugin descrevia "ações" de forma genérica (campo `actions` no manifest — ver [ADR-0017](0017-plugin-system.md)), sem uma unidade nomeada e padronizada por trás disso. Isso deixa implícito o que deveria ser explícito: uma Skill não é uma caixa preta com um método de entrada, é um conjunto de capacidades discretas, cada uma com seu próprio contrato de entrada/saída, seu próprio nível de risco e seu próprio requisito de autonomia.

## Decisão

Toda Skill é implementada como um conjunto de **Capabilities** — não funções soltas. Uma Capability tem nome (verbo-substantivo, ex: `CreateEvent`), schema de entrada, schema de saída (sempre no envelope de [ADR-0026](0026-envelope-de-resposta-padronizado.md)), e um nível mínimo de autonomia requerido (ver [ADR-0029](0029-autonomia-progressiva.md)). O campo `actions` do manifest de Plugin é renomeado para `capabilities`, mantendo a mesma posição no schema.

Exemplo — a Skill `GoogleCalendarSkill` (Plugin `calendar`) expõe as Capabilities `CreateEvent`, `CancelEvent`, `MoveEvent`, `SearchAgenda`.

## Consequências

- O Planner Engine e o Agent Engine podem raciocinar sobre "o que é possível fazer" em termos de Capabilities nomeadas e catalogáveis, não em termos de detalhes de implementação de cada Skill.
- Duas Skills diferentes podem implementar a mesma Capability (ex: `SendMessage` em `TelegramSkill` e em `WhatsAppSkill`) — abre caminho para o Planner escolher a Skill certa por contexto, não só por nome fixo.
- Cada Capability, tendo seu próprio nível de autonomia requerido, permite granularidade que uma permissão única por Skill não permitiria (ex: `SearchAgenda` pode ser Nível 2 — Delegado, enquanto `CancelEvent` exige Nível 1 — Assistido, dentro da mesma Skill).
- Exige atualizar `plugins/manifest.schema.json` e os manifests já existentes (`gestor`, `github`, `telegram`, `calendar`, `email`, `whatsapp`) para usar `capabilities` em vez de `actions` — mudança mecânica de nome de campo, sem alterar o significado do que já estava especificado.
