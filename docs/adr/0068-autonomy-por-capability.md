# ADR-0068: Autonomy é baseada em capability nomeada, não em nível numérico

- **Status**: Aceito — substitui, para o Identity Engine, a resolução por nível único de [ADR-0029](0029-autonomia-progressiva.md)
- **Data**: 2026-08-04

## Contexto

[ADR-0029](0029-autonomia-progressiva.md)/[SIGMA_PROTOCOL.md §5](../../SIGMA_PROTOCOL.md#5-autonomia-progressiva) definem Autonomia Progressiva como um único inteiro 0-3 por Role, comparado por "menor valor" contra o `autonomy_level_required` de uma Capability. O Product Owner apontou uma limitação real: um nível único não distingue "posso aprovar orçamento sozinho" de "posso apagar uma Mission sozinho" — duas ações de risco muito diferente podem cair sob o mesmo nível numérico, forçando o Role a ser mais permissivo ou mais restritivo do que deveria em alguma das duas.

## Decisão

`Role` (`packages/identity-engine/src/Domain/Role.php`) carrega `autonomyCapabilities: array<string, bool>` — um mapa de nomes de capability (`CanApproveBudget`, `CanDeleteMission`, `CanDeploy`, etc., nomeadas por quem define o Role) para `true`/`false`, em vez de um único inteiro. `Context::canAutonomously(string $capabilityKey): bool` (`packages/identity-engine/src/Domain/Context.php`) é o ponto de checagem — retorna `false` por padrão (nega por segurança) para qualquer capability não explicitamente concedida.

Resolução ao agregar múltiplos `RoleAssignment`s aplicáveis (`Identity::resolveContext()`): uma capability é `true` no `Context` final se **qualquer** Role aplicável a conceder como `true` — união (OR), mesma lógica já usada para Permissions (ter mais Roles só pode dar mais acesso, nunca menos). Isso é uma decisão de design desta ADR, não estava especificada pelo Product Owner explicitamente.

Este esquema roda em paralelo a `PermissionSet` — uma Capability do SIGMA pode, no futuro, checar tanto `Context::hasPermission()` (pode fazer, se confirmado manualmente) quanto `Context::canAutonomously()` (pode fazer sem confirmação). O `autonomy_level_required: 0-3` inteiro que hoje existe em todo Sigma Contract (ex: `contracts/Module.contract.yaml`, `contracts/Identity.contract.yaml`) não é removido por esta ADR — ele continua sendo o mecanismo de infraestrutura pura (Bootstrap, Release 2). Reconciliar os dois esquemas (nível numérico vs. capability nomeada) para Capabilities de domínio real é uma decisão adiada para quando o Skill Engine (Release 8) definir `Capability.contract.yaml` de verdade — sinalizado aqui, não resolvido.

## Consequências

- Um Role pode conceder autonomia granular por ação específica, em vez de um nível único que força trade-offs entre ações de risco muito diferente.
- Nega por padrão (`?? false`) — uma capability nunca listada é tratada como não autorizada, nunca o contrário.
- Cria uma divergência temporária entre o esquema numérico (`autonomy_level_required`, usado por infraestrutura pura) e o esquema por capability (usado pelo Identity Engine) — registrada explicitamente acima, a ser reconciliada na Release 8, não escondida.
- `RoleTest`/`ContextTest`/`IdentityTest` cobrem a resolução, incluindo o caso de uma capability nunca concedida por nenhum Role aplicável.
