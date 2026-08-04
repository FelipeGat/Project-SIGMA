# Sigma Contracts

Contrato formal entre módulos de domínio — não confundir com o `manifest.json` de um Plugin (empacotamento técnico) nem com [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md) (o formato de mensagem entre Engines). Um Contract descreve **um conceito de domínio**: o que entra, o que sai, quais eventos gera, quais permissões exige, quais versões suporta. Qualquer implementação que respeite o contrato pode substituir outra sem que o resto do sistema perceba. Ver [ADR-0049](../docs/adr/0049-sigma-contracts.md).

Formato de referência em [template.contract.yaml](template.contract.yaml).

## Contratos previstos

Um contrato só é escrito quando o conceito que ele descreve tem Release aprovada — escrever contrato de algo que ainda não existe geraria documentação fictícia (mesmo princípio já seguido em toda a Fase Foundation).

| Contrato | Nasce com | Status |
|---|---|---|
| [Module.contract.yaml](Module.contract.yaml) | Release 2 — SIGMA Bootstrap | ✅ Publicado |
| [Identity.contract.yaml](Identity.contract.yaml) | Release 3 — Identity Engine | ✅ Publicado — antes do código, ver [IDENTITY_MODEL.md](../IDENTITY_MODEL.md) |
| `Memory.contract.yaml` | Release 4 — Memory Engine | ⚪ Não iniciado |
| `Mission.contract.yaml` | Release 5 — Mission Engine | ⚪ Não iniciado |
| `Intent.contract.yaml` | Release 7 — Intent Engine | ⚪ Não iniciado |
| `Plugin.contract.yaml`, `Capability.contract.yaml` | Release 8 — Skill Engine | ⚪ Não iniciado |
| `Workspace.contract.yaml` | Quando Workspace ganhar Release própria (hoje modelado dentro do Identity Engine) | ⚪ Não iniciado |
