# ADR-0058: `manifestVersion` versiona o formato do System Manifest

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O [System Manifest](../../SYSTEM_MANIFEST.md) (ADR-0045) hoje só declara `project`, `version`, `modules`, `providers`, `workspace` — mas o próprio formato do arquivo vai crescer: `engines`/`plugins` completos chegam com Releases futuras, e outras Releases provavelmente vão querer acrescentar seções novas. Sem um campo que identifique a versão do **formato** (distinto de `version`, que é a versão do projeto SIGMA nele descrito), qualquer evolução futura do Manifest corre o risco de um `SystemManifestLoader` mais novo interpretar silenciosamente um arquivo de formato antigo (ou vice-versa) de forma incorreta, em vez de recusar explicitamente.

## Decisão

O System Manifest ganha um campo obrigatório `manifestVersion` (inteiro), distinto de `project`/`version`. `SystemManifestLoader` valida sua presença e o rejeita explicitamente (`manifest.missing_field`) se ausente ou não for um inteiro, e rejeita explicitamente (`manifest.unsupported_manifest_version`) qualquer valor que a versão corrente do loader não reconheça. Hoje só existe `manifestVersion: 1`; o loader desta Release só entende essa versão.

## Consequências

- Uma evolução futura do formato (novo campo obrigatório, mudança de estrutura) pode incrementar `manifestVersion` e o loader recusa explicitamente um Manifest do formato antigo em vez de tentar interpretá-lo incorretamente — mesmo princípio de "falhar explicitamente, nunca silenciosamente" já aplicado ao resto do Bootstrap (ADR-0044).
- Custo mínimo agora (um campo obrigatório a mais, um `system-manifest.yaml` de exemplo a atualizar) contra o custo de retrofitar versionamento depois que múltiplos ambientes já têm Manifests em produção sem esse campo.
- Não introduz nenhuma lógica de migração ou compatibilidade entre versões do formato — isso fica para quando `manifestVersion` realmente incrementar pela primeira vez.
