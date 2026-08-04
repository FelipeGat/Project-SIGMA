# ADR-0029: Princípio da Autonomia Progressiva — quatro níveis configuráveis

- **Status**: Aceito — supera o campo booleano `requires_human_approval` de [ADR-0017](0017-plugin-system.md)
- **Data**: 2026-08-04

## Contexto

O modelo de permissão original tratava autorização como binário: uma operação de uma Skill exigia ou não aprovação humana (`requires_human_approval: true/false`), fixo por operação. Isso não escala para um sistema que vai integrar WhatsApp, Telegram, GitHub, Gestor.Alfa e outros: nem toda ação deve ser automática, e nem todo usuário deveria ter o mesmo nível de autonomia permitido — um binário único não captura essa variação.

## Decisão

O SIGMA opera com quatro níveis de autonomia, configuráveis por User/Role (ver [MULTITENANCY.md](../../MULTITENANCY.md)) e exigidos por Capability (ver [ADR-0027](0027-capability-unidade-de-skill.md)):

| Nível | Nome | Comportamento |
|---|---|---|
| 0 | Consultivo | O SIGMA apenas sugere ações — nunca executa |
| 1 | Assistido | Executa somente após confirmação explícita, chamada a chamada |
| 2 | Delegado | Executa sem confirmação para Capabilities previamente autorizadas (ex: registrar uma reunião, atualizar um CRM) |
| 3 | Operacional | Orquestra processos completos (múltiplas Missions de uma mesma Intent) dentro de limites definidos, sem confirmação por etapa |

**Regra de resolução**: o nível efetivo de uma chamada é o **menor** entre o nível configurado do User/Role que originou a Intent e o nível mínimo exigido pela Capability sendo invocada. Uma Capability nunca executa acima do nível permitido ao usuário, mesmo que ele peça explicitamente — e um usuário em Nível 3 ainda respeita o nível mínimo de uma Capability específica marcada como sempre-Nível-1 (ex: uma ação financeira irreversível pode exigir confirmação mesmo de um usuário Operacional).

## Consequências

- Substitui `requires_human_approval` (booleano) por `autonomy_level_required` (0–3) em cada Capability — os manifests de Plugin já existentes (`plugins/*/manifest.json`) precisam desta migração de campo.
- Permite configurar autonomia por combinação de User/Role e Capability sem criar uma matriz de permissões ad-hoc — a mesma pessoa pode ser Nível 3 para Capabilities de leitura e Nível 1 para Capabilities financeiras.
- É a peça que torna a decomposição de uma Intent em múltiplas Missions ([ADR-0028](0028-intencao-nao-comando.md)) segura em produção: mesmo quando o Planner decide fazer quatro coisas a partir de uma frase, cada uma respeita seu próprio gate de autonomia.
- Adiciona complexidade de configuração (quem define o nível de cada User/Role, quem define o nível mínimo de cada Capability) — trabalho a ser detalhado no épico correspondente (Release 9 — Agent Engine e Release 8 — Skill Engine, onde autonomia é de fato aplicada), não nesta ADR.
