# Identity Engine — Version

Semantic Versioning por Engine, não só por projeto — entrega da Release 3.5 (ver [ADR-0077](../../docs/adr/0077-version-md-e-semver-por-engine.md)). O projeto SIGMA como um todo tem sua própria versão ([system-manifest.yaml](../../system-manifest.yaml), campo `version`); cada Engine, a partir de agora, também tem a sua — a de um pode subir sem a do outro mudar.

## Versão atual

**1.0.0** — já era o valor declarado em `composer.json` desde a Release 3A; este documento formaliza o que essa versão significa e o processo para a próxima.

## O que está em 1.0.0

- **Domain** (Release 3A): `Identifier` + 9 Value Objects de identificador, `Tenant`/`Company`/`Workspace`/`User`/`Team`/`Permission`/`Scope`/`Role`/`RoleAssignment`/`Session`/`Context`/`Identity` (agregado raiz), 10 eventos de domínio.
- **Application** (Release 3B): 9 interfaces de repositório, `CredentialProvider` (renomeado de `PasswordHasher` na Release 3.5, ver [ADR-0072](../../docs/adr/0072-credentialprovider-substitui-passwordhasher.md)), 9 casos de uso.
- **Infrastructure** (Release 3B): `MigrationRunner`, 9 repositórios `Pdo*`, `Argon2idCredentialProvider`.
- **Interfaces** (Release 3B): `IdentityEngineModule implements IModule`.

## Eventos publicados nesta versão

Ver [EVENT_CATALOG.md — Identity Engine](../../EVENT_CATALOG.md#identity-engine) — todos em `v1`.

## Contracts desta versão

- [contracts/Identity.contract.yaml](../../contracts/Identity.contract.yaml) — `version: "1.0.0"`, `supported_versions: ["1.0.0"]`.

## Breaking Changes

Nenhuma ainda — primeira versão.

## Política de versionamento

- **PATCH** (`1.0.x`): correção sem mudar contrato público (interfaces de `Application/`, eventos, `Identity.contract.yaml`) — ex: bug em `Infrastructure/` que não muda comportamento observável.
- **MINOR** (`1.x.0`): capacidade nova, aditiva, sem quebrar quem já consome (ex: um novo caso de uso, um novo evento).
- **MAJOR** (`x.0.0`): mudança incompatível em `Application/` (assinatura de caso de uso, interface de repositório) ou em `Identity.contract.yaml` — exige atualizar `supported_versions` e, normalmente, uma ADR própria.

Mudança de versão de `Domain/`/`Application/` sempre correlaciona com uma entrada nova em `Breaking Changes` acima quando for MAJOR — nunca uma versão nova sem o motivo registrado.
