# ADR-0033: Capability Registry — versão, owner e dependências por Capability

- **Status**: Aceito — estende [ADR-0027](0027-capability-unidade-de-skill.md)
- **Data**: 2026-08-04

## Contexto

[ADR-0027](0027-capability-unidade-de-skill.md) definiu Capability como a unidade de implementação de uma Skill, mas sem metadados suficientes para catalogar Capabilities de forma confiável à medida que o número delas cresce: quem é responsável por uma Capability quando algo quebra, se uma versão específica dela é compatível com o que um Agent espera, e se ela depende de outra Capability já existir.

## Decisão

Cada Capability, no manifest de Plugin, ganha `version` (semver, independente da versão do Plugin), `owner` (responsável) e `dependencies` (nomes de outras Capabilities necessárias). O conjunto de todas as Capabilities de todos os Plugins carregados forma o **Capability Registry**, mantido pelo Skill Engine (Release 7). Ver [SIGMA_PROTOCOL.md §4](../../SIGMA_PROTOCOL.md#4-capability-e-capability-registry).

## Consequências

- Uma Capability pode evoluir de versão sem forçar todo o Plugin a subir de versão junto — reduz o raio de impacto de uma mudança.
- `dependencies` permite ao Skill Engine recusar o carregamento de uma Capability cuja dependência não está presente, em vez de falhar em runtime na primeira invocação.
- O Capability Registry se torna a fonte de consulta natural para o Planner Engine saber "o que é possível fazer" no sistema — capacidade adicional sobre a qual o épico do Planner (Release 5) pode se apoiar, mesmo que o Registry em si só exista de fato na Release 7.
- Os 6 manifests de Plugin já especificados (`gestor`, `github`, `telegram`, `calendar`, `email`, `whatsapp`) e o schema formal foram migrados nesta mesma revisão para incluir os novos campos.
