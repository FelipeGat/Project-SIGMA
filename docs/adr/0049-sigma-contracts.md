# ADR-0049: Sigma Contracts — contrato formal por conceito de domínio

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

SIGMA Protocol define o formato de mensagem entre Engines; o manifest de Plugin define o empacotamento técnico de uma Skill. Faltava um contrato no nível do **conceito de domínio** em si — o que uma Mission aceita e produz, independente de qual Mission Engine específico a implementa; o mesmo para Plugin, Capability, Workspace, Intent, Memory. Sem isso, "substituir a implementação sem quebrar o resto do sistema" (princípio central do [MANIFESTO.md](../../MANIFESTO.md)) fica sem um documento formal contra o qual verificar a substituição.

## Decisão

Cria-se `contracts/` — um contrato YAML por conceito de domínio (`Mission.contract.yaml`, `Plugin.contract.yaml`, `Capability.contract.yaml`, `Workspace.contract.yaml`, `Intent.contract.yaml`, `Memory.contract.yaml`, entre outros conforme necessário), declarando: entrada, saída, eventos gerados, permissões exigidas, versões suportadas. Formato de referência em [contracts/template.contract.yaml](../../contracts/template.contract.yaml). Um contrato só é escrito quando o conceito correspondente tem Release aprovada.

## Consequências

- Qualquer implementação que respeite o contrato de um conceito pode substituir outra — o teste de "está desacoplado de verdade" passa a ter um artefato formal contra o qual validar, não só um princípio declarado.
- A Architecture Validation de cada Release ([ADR-0054](0054-tres-niveis-de-validacao.md)) inclui verificar que a implementação respeita os Contracts relevantes.
- Nenhum contrato existe ainda — nascem junto com a Release do conceito que descrevem (ver tabela em [contracts/README.md](../../contracts/README.md)).
